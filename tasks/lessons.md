# Lessons Learned

## General
- Always check PHP path on Windows/XAMPP environments.
- Use Sentence Case for all texts.

## Technical
- **Constructor check**: When refactoring or creating controllers, always ensure dependencies are injected via a constructor if they are called as member properties.
- **Role visibility in tabs**: When implementing tab-based list views, carefully review the `if` blocks for role-based actions so they are not accidentally removed during layout changes.
- **JS iteration safety**: When iterating over API response objects, always filter by valid keys (whitelist) instead of `Object.keys(data)` if the response includes standard success/message keys.
- **CSRF in Fetch**: When using `fetch()` for POST requests in views, always include the `X-CSRF-TOKEN` header. Use `CsrfHelper::generateToken()` to populate it.
- **Role Normalization**: Databases might store roles with inconsistent casing (e.g., "Tecnico_imagenes" vs "farmaceutico"). Always use `strtolower()` before using roles as keys in maps or conditional blocks in the frontend.
- **Service Dependency**: Invoicing for new modules (like Imaging) must be explicitly added to `BillingService` and called from `AppointmentsService` to maintain consistency with `Laboratory` patterns.
- **Foreign Key Constraints on Walk-ins**: When automatically creating clinical records (citas, historial) for walk-in patients, ensure `medico_id` is nullable. If not, avoid creating placeholder records at generation time and handle registration at the moment of attention (Option A).
- **JavaScript Function Wrapping**: Always ensure that all global code in scripts is properly wrapped in functions. A single missing function header or closing brace can invalidate the entire script and break unrelated functionality (e.g., buttons not responding).
- **UI Tool Fallbacks**: When using custom UI utilities like `showToast`, always include a local fallback (e.g., using `alert`) in case the main notification library fails to load or the script has syntax errors.
- **Silent Test Failures**: If PHPUnit terminates without a summary (no "OK" or "FAIL"), it likely hit an `exit()` or `die()` call. A common source is calling `AuthHelper::checkRole()` or `UrlHelper::redirect()` (which calls `exit()`) inside constructors of services being tested. Ensure `tests/bootstrap.php` sets all required session variables like `$_SESSION['user_role']` and `$_SESSION['last_activity']` to avoid unauthorized redirects during test discovery or initialization.
- **PDO Named Parameters**: Never reuse the same named parameter (e.g., `:medico_id`) multiple times in a single SQL query when `PDO::ATTR_EMULATE_PREPARES` is false (native prepares). This causes `SQLSTATE[HY093]: Invalid parameter number`. Instead, use distinct names like `:medico_id1`, `:medico_id2` and bind the value to each.
- **Redirect Loop Prevention**: In controllers, avoid blind redirects to the login page on error (`catch` blocks). If the user is already authenticated but the dashboard fails, the login page might redirect back to the dashboard, causing an infinite loop. Instead, display a clear error message or a dedicated error page.
- **Optional Repository Filters**: Repository methods like `getInternamientosActivos()` should be flexible. If a service passes a filter (like `medico_id`), the repository method should accept it as an optional parameter and adjust the SQL accordingly instead of ignoring it.
