<?php
namespace App\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('notice')]
class Notice
{
    public String $message;
    public String $label;
}