<?php

namespace IForm\prebuilt_forms;

/**
 * Classification of the different types of prebuilt_form.
 */
enum PageType {
  // Any page for viewing raw or summarised data.
  case Report;
  // Any page for any data entry.
  case DataEntry;
  // Any non-reporting or data entry pages.
  case Utility;
}
