<?php

/*
 * This file is part of the symfony package.
 * (c) Jonathan H. Wage <jonwage@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfDoctrineRecordI18nFilter implements access to the translated properties for
 * the current culture from the internationalized model.
 *
 * @package    symfony
 * @subpackage doctrine
 * @author     Jonathan H. Wage <jonwage@gmail.com>
 * @version    SVN: $Id: sfDoctrineRecordI18nFilter.class.php 11878 2008-09-30 20:14:40Z Jonathan.Wage $
 */
class dmDoctrineRecordI18nFilter extends Doctrine_Record_Filter
{
	public function init()
	{
	}

	/**
	 * Implementation of filterSet() to call set on Translation relationship to allow
	 * access to I18n properties from the main object.
	 *
	 * @param Doctrine_Record $record
	 * @param string $name Name of the property
	 * @param string $value Value of the property
	 * @return void
	 */
	public function filterSet(Doctrine_Record $record, $fieldName, $value)
	{
		$i18n = $record['Translation'][myDoctrineRecord::getDefaultCulture()];
		
    if(!ctype_lower($fieldName) && !$i18n->contains($fieldName))
    {
      $underscoredFieldName = dmString::underscore($fieldName);
      if (strpos($underscoredFieldName, '_') !== false && $i18n->contains($underscoredFieldName))
      {
        return $i18n->set($underscoredFieldName, $value);
      }
    }
		
		return $i18n->set($fieldName, $value);
	}

	/**
	 * Implementation of filterGet() to call get on Translation relationship to allow
	 * access to I18n properties from the main object.
	 *
	 * @param Doctrine_Record $record
	 * @param string $name Name of the property
	 * @param string $value Value of the property
	 * @return void
	 */
	public function filterGet(Doctrine_Record $record, $fieldName)
	{
		$culture = myDoctrineRecord::getDefaultCulture();
		
		$translation = $record->get('Translation');
		
		if (isset($translation[$culture]))
		{
			$i18n = $translation[$culture];
		}
		else
		{
			$i18n = $translation[sfConfig::get('sf_default_culture')];
		}
	
    if(!ctype_lower($fieldName) && !$i18n->contains($fieldName))
    {
      $underscoredFieldName = dmString::underscore($fieldName);
      if (strpos($underscoredFieldName, '_') !== false && $i18n->contains($underscoredFieldName))
      {
        return $i18n->get($underscoredFieldName);
      }
    }

		return $i18n->get($fieldName);
	}
}