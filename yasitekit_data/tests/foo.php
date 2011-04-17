<?php
class A {
 public $x = 5;
}
$a = new A();
var_dump($a);
var_dump($a->x);
var_dump($a->{x});

