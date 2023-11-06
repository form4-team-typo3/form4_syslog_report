# Known Issues

```
PHP Warning: vsprintf(): Too few arguments in typo3/sysext/belog/Classes/ViewHelpers/FormatDetailsViewHelper.php line 44
```

The extension uses some functions and viewhelpers from the belog core extension.
The viewhelper **`FormatDetailsViewHelper`** does not check if there is logData for substitution available. Therefore there can be warnings when executing the command.
