# Code block checker

Make sure that code blocks have valid syntax and can actually run.

```terminal
$ ./code-block-checker.php verify:docs /path/to/docs cache.rst controller.rst --env=dev

::error file=cache,line=377::[Invalid syntax] PHP Parse error:  syntax error, unexpected token "}"

 [ERROR] Build completed with 1 errors


$ ./code-block-checker.php verify:docs /path/to/docs cache.rst controller.rst --env=dev --generate-baseline=baseline.json
$ ./code-block-checker.php verify:docs /path/to/docs cache.rst controller.rst --env=dev --baseline=baseline.json

 [OK] Build completed successfully!

```

