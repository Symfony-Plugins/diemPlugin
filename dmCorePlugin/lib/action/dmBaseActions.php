<?php

abstract class dmBaseActions extends sfActions
{
  protected function forwardSecureUnless($condition)
  {
    if (!$condition)
    {
      return $this->forwardSecure();
    }
  }

  protected function forwardSecure()
  {
    return $this->forward(sfConfig::get('sf_secure_module'), sfConfig::get('sf_secure_action'));
  }

  /**
   * Appends the given json to the response content and bypasses the built-in view system.
   *
   * This method must be called as with a return:
   *
   * <code>return $this->renderJson(array('key'=>'value'))</code>
   * 
   * Important : due to a limitation of the jquery form plugin (http://jquery.malsup.com/form/#file-upload)
   * when a file have been uploaded, the contentType is set to text/html
   * and the json response is wrapped into a textarea
   *
   * @param string $json Json to append to the response
   *
   * @return sfView::NONE
   */
  public function renderJson($json)
  {
    $this->response->clearJavascripts();
    $this->response->clearStylesheets();
    $this->setLayout(false);
    sfConfig::set('sf_web_debug', false);
    
    $encodedJson = json_encode($json);
    
    if ($this->request->isMethod('post') && $this->request->isXmlHttpRequest() && !in_array('application/json', $this->request->getAcceptableContentTypes()))
    {
      $this->response->setContentType('text/html');
      $this->response->setContent('<textarea>'.$encodedJson.'</textarea>');
    }
    else
    {
      $this->response->setContentType('application/json');
      $this->response->setContent($encodedJson);
    }

    return sfView::NONE;
  }

  protected function renderAsync(array $parts, $encodeAssets = false)
  {
    $parts = array_merge(array('html' => '', 'css' => array(), 'js' => array()), $parts);
    
    // translate asset aliases to web paths
    foreach($parts['css'] as $index => $asset)
    {
      $parts['css'][$index] = $this->getHelper()->getStylesheetWebPath($asset);
    }
    foreach($parts['js'] as $index => $asset)
    {
      $parts['js'][$index] = $this->getHelper()->getJavascriptWebPath($asset);
    }

    if(!empty($parts['css']) || !empty($parts['js']))
    {
      if ($encodeAssets)
      {
        $parts['html'] .= $this->getHelper()->tag('div.dm_encoded_assets.none', json_encode(array(
          'css' => $parts['css'],
          'js'  => $parts['js']
        )));
      }
      else
      {
        foreach($parts['css'] as $css)
        {
          $parts['html'] .= '<link rel="stylesheet" type="text/css" href="'.$css.'"/>';
        }

        foreach($parts['js'] as $js)
        {
          $parts['html'] .= '<script type="text/javascript" src="'.$js.'"></script>';
        }
      }
    }

    $this->response->setContentType('text/html');
    $this->response->setContent($parts['html']);

    return sfView::NONE;
  }
  
  protected function redirectBack()
  {    
    return $this->redirect($this->getBackUrl());
  }
  
  protected function getBackUrl()
  {
    $backUrl = $this->request->getReferer();

    if (!$backUrl || ($backUrl == $this->request->getUri() && $this->request->isMethod('get')))
    {
      $backUrl = '@homepage';
    }
    
    return $backUrl;
  }
  
  public function getRouting()
  {
    return $this->context->getRouting();
  }
  
  public function getHelper()
  {
    return $this->context->getHelper();
  }

  public function getI18n()
  {
    return $this->context->getI18n();
  }
  
  public function getServiceContainer()
  {
    return $this->context->getServiceContainer();
  }
  
  public function getService($serviceName, $class = null)
  {
    return $this->getServiceContainer()->getService($serviceName, $class);
  }
  
  /**
   * To download a file using its absolute path or raw data
   *
   * @param mixed $pathOrData path to file or raw data
   * @param array $options    optional information file_name and type
   */
  protected function download($pathOrData, array $options = array())
  {
    if (is_readable($pathOrData))
    {
      $data = file_get_contents($pathOrData);

      if(empty($options['file_name']))
      {
        $options['file_name'] = basename($pathOrData);
      }
    }
    else
    {
      $data = $pathOrData;

      if(empty($options['file_name']))
      {
        $options['file_name'] = dmString::slugify(dmConfig::get('site_name')).'-'.dmString::random(8);
      }
    }
    
    if (!isset($options['type']))
    {
      $options['type'] = $this->getService('mime_type_resolver')->getByFilename($options['file_name']);
    }

    //Gather relevent info about file
    $fileLenght = strlen($data);

    //Begin writing headers
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: public");
    header("Content-Description: File Transfer");

    //Use the switch-generated Content-Type
    header('Content-Type: '.$options['type']);

    //Force the download
    header("Content-Disposition: attachment; filename=\"".$options['file_name']."\";");
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: ".$fileLenght);
    
    print $data;
    exit;
  }
  
  /**
   * Calls methods defined via sfEventDispatcher.
   *
   * @param string $method The method name
   * @param array  $arguments The method arguments
   *
   * @return mixed The returned value of the called method
   *
   * @throws sfException If called method is undefined
   */
  public function __call($method, $arguments)
  {
    $event = $this->dispatcher->notifyUntil(new sfEvent($this, 'action.method_not_found', array('method' => $method, 'arguments' => $arguments)));
    if (!$event->isProcessed())
    {
      throw new sfException(sprintf('Call to undefined method %s::%s.', get_class($this), $method));
    }

    return $event->getReturnValue();
  }
}