<?php

class dmMediaFlashTag extends dmMediaTag
{

  public function render()
  {
    $tag = '<div'.$this->getHtmlAttributes().'></div>';

    return $tag;
  }

}