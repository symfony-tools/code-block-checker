# Symfony Code Block Checker

Makes sure that code blocks have valid syntax and can actually run.

```terminal
$ php ./code-block-checker.php verify:docs /path/to/docs cache.rst controller.rst --env=dev

::error file=cache,line=377::[Invalid syntax] PHP Parse error:  syntax error, unexpected token "}"

 [ERROR] Build completed with 1 errors


$ php ./code-block-checker.php verify:docs /path/to/docs cache.rst controller.rst --env=dev --generate-baseline=baseline.json
$ php ./code-block-checker.php verify:docs /path/to/docs cache.rst controller.rst --env=dev --baseline=baseline.json

 [OK] Build completed successfully!

```

This project is considered **an internal tool** and therefore, you
**shouldn't use this project in your application**. Unlike the rest of the
Symfony projects, this repository doesn't provide any support and it doesn't
guarantee backward compatibility either. Any or the entire project can change,
or even disappear, at any moment without prior notice.
