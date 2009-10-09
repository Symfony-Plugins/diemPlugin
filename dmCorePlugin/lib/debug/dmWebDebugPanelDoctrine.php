<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 * (c) Jonathan H. Wage <jonwage@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfWebDebugPanelDoctrine adds a panel to the web debug toolbar with Doctrine information.
 *
 * @package    symfony
 * @subpackage debug
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author     Jonathan H. Wage <jonwage@gmail.com>
 * @version    SVN: $Id: sfWebDebugPanelDoctrine.class.php 11205 2008-08-27 16:24:17Z fabien $
 */
class dmWebDebugPanelDoctrine extends sfWebDebugPanelDoctrine
{

  /**
   * Builds the sql logs and returns them as an array.
   *
   * @return array
   */
  protected function getSqlLogs()
  {
    $logs = $this->webDebug->getLogger()->getLogs();

    $html = array();
    foreach ($this->getDoctrineEvents() as $i => $event)
    {
      $conn = $event->getInvoker() instanceof Doctrine_Connection ? $event->getInvoker() : $event->getInvoker()->getConnection();
      $params = sfDoctrineConnectionProfiler::fixParams($event->getParams());
      $query = $this->formatSql(htmlspecialchars($event->getQuery(), ENT_QUOTES, sfConfig::get('sf_charset')));

      // interpolate parameters
      foreach ($params as $param)
      {
        $query = join(var_export(is_scalar($param) ? $param : (string) $param, true), explode('?', $query, 2));
      }

      // slow query
      if ($event->slowQuery && $this->getStatus() > sfLogger::NOTICE)
      {
        $this->setStatus(sfLogger::NOTICE);
      }

      // backtrace
      $backtrace = null;
      foreach ($logs as $i => $log)
      {
        if (!$log['debug_backtrace'])
        {
          // backtrace disabled
          break;
        }

        if (false !== strpos($log['message'], $event->getQuery()))
        {
          // assume queries are being requested in order
          unset($logs[$i]);
          $backtrace = '&nbsp;'.$this->getToggleableDebugStack($log['debug_backtrace']);
          break;
        }
      }

      $html[] = sprintf('
        <li class="%s">
          <p class="sfWebDebugDatabaseQuery">%s</p>
          <div class="sfWebDebugDatabaseLogInfo">%s ms, "%s" connection%s</div>
        </li>',
        $event->slowQuery ? 'sfWebDebugWarning' : '',
        $query,
        number_format($event->getElapsedSecs()*1000, 2),
        $conn->getName(),
        $backtrace
      );
    }

    return $html;
  }
}
