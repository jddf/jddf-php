<?php

declare(strict_types=1);

namespace Jddf;

abstract class Form {
  public static $EMPTY = "empty";
  public static $REF = "ref";
  public static $TYPE = "type";
  public static $ENUM = "enum";
  public static $ELEMENTS = "elements";
  public static $PROPERTIES = "properties";
  public static $VALUES = "values";
  public static $DISCRIMINATOR = "discriminator";
}
