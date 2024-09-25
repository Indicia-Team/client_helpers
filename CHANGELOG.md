# Indicia Client Helpers developer change log

This document describes changes to the code structure for developers and is not intended as a
complete feature change log. The full change log is available in the GitHub releases.

## Version 9.4.0
*2024-09-25*

* Some restructuring of code, with a new PSR4 style class autoloader. As a result, any custom
  prebuilt forms should implement the `IForm\prebuilt_forms\PrebuiltFormInterface` interface and
  the getPageType() method in order to define whether a data entry, reporting or utility page. This
  is not a breaking change - custom prebuilt forms that don't implement this interface will still
  work but will appear in the list of forms available when creating a container group using the
  `group_edit` form even if not a report.
* Prebuilt forms can remove the `isDataEntryForm()` as it is replaced by the
  `IForm\prebuilt_forms\PrebuiltFormInterface::getPageType()` method.
* There is a new autoloader - include the `autoload.php` file in the root folder to use it. This
  is currently only needed to load the `IForm\IndiciaConversions` class.
* The function `helper_base::ago` has been replaced by
  `IForm\IndiciaConversions::timestampToTimeAgoString`.
* The function `VerificationHelper::getStatusLabel` has been replaced by
  `IForm\IndiciaConversions::statusToLabel`.
* The function `VerificationHelper::getTranslatedStatusTerms` has been replaced by
  `IForm\IndiciaConversions::getTranslatedStatusTerms`.
* The function `VerificationHelper::getStatusIcons` has been replaced by
  `IForm\IndiciaConversions::statusToIcons`.