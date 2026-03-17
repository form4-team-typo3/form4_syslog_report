# Known Issues

## Resolved Issues

### FormatDetailsViewHelper vsprintf() Warning (Fixed in TYPO3 12.4)

**Previous Issue (TYPO3 11.5 and earlier):**
```
PHP Warning: vsprintf(): Too few arguments in typo3/sysext/belog/Classes/ViewHelpers/FormatDetailsViewHelper.php line 44
```

The extension uses some functions and viewhelpers from the belog core extension.
In TYPO3 11.5 and earlier versions, the viewhelper **`FormatDetailsViewHelper`** did not check if there is logData for substitution available, which could cause warnings when executing the command.

**Status:** ✅ **RESOLVED in TYPO3 12.4**

In TYPO3 12.4, the `vsprintf()` call in `LogDataTrait::formatLogDetailsStatic()` is now wrapped in a try-catch block that handles `\ValueError` and `\ArgumentCountError` exceptions. This prevents warnings when there are insufficient arguments for placeholders in log detail strings.