<?php

namespace IForm\prebuilt_forms;

/**
 * A common interface for Indicia prebuilt forms.
 */
interface PrebuiltFormInterface {

  /**
   * Define the purpose of the page.
   *
   * @return PageType
   *   E.g. DataEntry, Report, or Utility.
   */
  public static function getPageType(): PageType;

}
