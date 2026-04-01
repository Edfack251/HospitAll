# Task: Fix Doctor Dashboard Redirect Loop and Logic Issues

## Plan
1. [x] Fix duplicate named parameters in `LaboratoryRepository::getResultadosPendientes`
2. [x] Audit other repositories for duplicate named parameters
3. [x] Fix redirect loop in `DoctorDashboardController`
4. [x] Add optional `medico_id` filter to `HospitalizationRepository::getInternamientosActivos`
5. [x] Verify fixes with PHPUnit and manual check logs

## Diagnosis Summary
- `LaboratoryRepository::getResultadosPendientes` uses `:medico_id` twice in a single query, causing `SQLSTATE[HY093]` on some PDO configurations.
- `DoctorDashboardController` redirects to `login` on error, but `login` redirects to dashboard if authenticated, creating an infinite loop.
- `HospitalizationRepository::getInternamientosActivos` ignores the `$medico_id` passed by the service, showing all patients.

## Verification
- Run `C:\xampp\php\php.exe vendor\bin\phpunit`
- Check Apache error logs for `HY093`
