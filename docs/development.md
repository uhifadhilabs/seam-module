# Development

```bash
composer install
composer check   # cs:check -> phpstan (max) -> the suite
```

- PHP 8.4+, PHPStan level **max**, php-cs-fixer `@Symfony` + `@Symfony:risky`.
- **Tests first, always.** A behaviour change starts as a failing test naming
  the class or service id it wants; the change is the commit that makes it
  pass. CI gates on `composer check`: one suite, one verdict, and a failure
  there is a failure.
- `tests/Integration/TestKernel.php` is the seam alone — framework, doctrine,
  this bundle — and opens no connection, which is what a host that has not
  migrated yet must still be able to boot.
  `tests/Integration/Fixtures/HostKernel.php` adds a stand-in host on top and
  does connect, because the seam genuinely owns tables:
  `postgresql://app:app@127.0.0.1:5434/seam_bundle_test` on the fundi cluster.
