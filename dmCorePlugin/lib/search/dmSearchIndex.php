<?php

require_once('Zend/Search/Lucene.php');

class dmSearchIndex extends dmSearchIndexCommon
{
  protected
  $luceneIndex;
  
  protected function initialize(array $options)
  {
    parent::initialize($options);
    
    if (!$this->getOption('dir'))
    {
      throw new dmSearchIndexException('Can not create an index without dir option');
    }
    
    $this->createLuceneIndex();
  }
  
  public function getFullPath()
  {
    return dmProject::rootify($this->getOption('dir'));
  }

  protected function createLuceneIndex()
  {
    if (file_exists(dmOs::join($this->getFullPath(), 'segments.gen')))
    {
      try
      {
        $this->luceneIndex = Zend_Search_Lucene::open($this->getFullPath());
      }
      catch(Zend_Search_Lucene_Exception $e)
      {
        $this->erase();
      }
    }
    else
    {
      $this->luceneIndex = Zend_Search_Lucene::create($this->getFullPath());
    }
  }

  public function getCulture()
  {
    return $this->getOption('culture');
  }
  
  public function setCulture($culture)
  {
    return $this->setOption('culture', $culture);
  }

  public function search($query)
  {
    if (!$query instanceof Zend_Search_Lucene_Search_Query)
    {
      $query = $this->getLuceneQuery($this->cleanText($query));
    }

    $luceneHits = $this->luceneIndex->find($query);
    $hits = array();
    foreach($luceneHits as $hit)
    {
      $this->serviceContainer->setParameter('search_hit.score', $hit->score);
      $this->serviceContainer->setParameter('search_hit.page_id', $hit->page_id);
      $hits[] = $this->serviceContainer->getService('search_hit');
    }
    unset($luceneHits);

    return $hits;
  }

  protected function getLuceneQuery($query)
  {
    $words = str_word_count($query, 1);
    
    $query = new Zend_Search_Lucene_Search_Query_Boolean();
    
    foreach($words as $word)
    {
      $term = new Zend_Search_Lucene_Index_Term($word);
      $subQuery = new Zend_Search_Lucene_Search_Query_Fuzzy($term, 0.4);
      $query->addSubquery($subQuery, true);
    }
    
    return $query;
    
    //  return Zend_Search_Lucene_Search_QueryParser::parse($query);
//    $term = new Zend_Search_Lucene_Index_Term($query);
//    return new Zend_Search_Lucene_Search_Query_Fuzzy($term, 0.4);
  }

  public function populate()
  {
    $start  = microtime(true);
    $logger = $this->serviceContainer->getService('logger');
    $user   = $this->serviceContainer->getService('user');
    
    $logger->log($this->getName().' : Populating index...');

    $this->erase();
    $logger->log($this->getName().' : Index erased.');
    
    $this->serviceContainer->mergeParameter('search_document.options', array(
      'culture' => $this->getCulture()
    ));
    
    $pages = $this->getPagesQuery()->fetchRecords();
    
    if (!count($pages))
    {
      $logger->log($this->getName().' : No pages to populate the index');
      return;
    }
    
    $oldCulture = $user->getCulture();
    $user->setCulture($this->getCulture());

    $nb = 0;
    $nbMax = count($pages);
    foreach ($pages as $page)
    {
      ++$nb;
      $logger->log($this->getName().' '.$nb.'/'.$nbMax.' : /'.$page->get('slug'));
      
      $this->serviceContainer->setParameter('search_document.source', $page);
      
      $this->luceneIndex->addDocument($this->serviceContainer->getService('search_document')->populate());
    }
    
    $user->setCulture($oldCulture);

    $time = microtime(true) - $start;

    $logger->log($this->getName().' : Index populated in "' . round($time, 2) . '" seconds.');

    $logger->log($this->getName().' : Time per document "' . round($time / count($pages), 3) . '" seconds.');

    $this->serviceContainer->get('dispatcher')->notify(new sfEvent($this, 'dm.search.populated', array(
      'culture' => $this->getCulture(),
      'name' => $this->getName(),
      'nb_documents' => count($pages),
      'time' => $time
    )));
    
    unset($pages);
    
    $this->fixPermissions();
  }

  public function optimize()
  {
    $start = microtime(true);
    $logger = $this->serviceContainer->getService('logger')->log($this->getName().' : Optimizing index...');
    
    $this->luceneIndex->optimize();
    
    $this->fixPermissions();

    $logger = $this->serviceContainer->getService('logger')->log($this->getName().' : Index optimized in "' . round(microtime(true) - $start, 2) . '" seconds.');
  }

  protected function erase()
  {
    $this->serviceContainer->getService('filesystem')->deleteDirContent($this->getFullPath());
    
    $this->createLuceneIndex();
  }

  public function getPagesQuery()
  {
    return dmDb::table('DmPage')
    ->createQuery('p')
    ->withI18n($this->getCulture())
    ->where('pTranslation.is_active = ?', true)
    ->andWhere('pTranslation.is_secure = ?', false)
    ->andWhere('p.module != ? OR ( p.action != ? AND p.action != ? AND p.action != ?)', array('main', 'error404', 'search', 'login'));
  }

  /*
   * @return array of words we do not want to index ( like "the", "a", to"... )
   */
  public function getStopWords()
  {
    return str_word_count(strtolower(dmConfig::get('search_stop_words')), 1);
  }

  public function describe()
  {
    return array(
      'Documents' => $this->luceneIndex->numDocs(),
      'Size'      => dmOs::humanizeSize($this->getByteSize())
    );
  }
  
  
  /*
   * @return Zend_Search_Lucene_Proxy instance
   */
  public function getLuceneIndex()
  {
    return $this->luceneIndex;
  }

  public static function cleanText($text)
  {
    return trim(
    preg_replace('|\s{2,}|', ' ',
    preg_replace('|\W|', ' ',
    strtolower(
    dmString::transliterate(
    strip_tags(
    str_replace(array("\n", '<'), array(' ', ' <'), $text)
    )
    )
    )
    )
    )
    );
  }

  /**
   * Gets the byte size of the index.
   *
   * @returns int The size in bytes
   */
  public function getByteSize()
  {
    $size = 0;
    foreach (new DirectoryIterator($this->getFullPath()) as $node)
    {
      if (!in_array($node->getFilename(), array('CVS', '.svn', '_svn')))
      {
        $size += $node->getSize();
      }
    }

    return $size;
  }
}