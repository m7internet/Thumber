<?php
include $_SERVER['DOCUMENT_ROOT'].'/Thumber.php';

$thumb = new Thumber();

$thumb
	->allowSize('1920x1080')->allowSize('1024x512')->allowSize('800x400')->allowSize('400x200')->allowSize('200x100')
    ->setNoImage('/media/no-image.jpg')
    ->source('/media')
    ->destination('/thumbs')
    ->debug(false)
    ->output()
;