<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Abstract base controller.
 *
 * All controllers must extend this class.
 * Provides access to the current Request and a view renderer.
 */
abstract class Controller
{
    /**
     * @param Request $request The current HTTP request (injected by the Router).
     */
    public function __construct(protected readonly Request $request) {}

    /**
     * Renders a view template inside the main layout.
     *
     * Variables in $data are extracted into the view and layout scope.
     * The view's output is captured via output buffering and made available
     * as $content inside the layout file.
     *
     * @param string $view Relative path to the view (e.g. 'dashboard/index').
     *                     Resolved to app/Views/{view}.php.
     * @param array  $data Variables to pass to the view and layout.
     */
    protected function render(string $view, array $vars = []): never
    {
        $viewPath = BASE_PATH . '/app/Views/' . $view . '.php';

        if (!file_exists($viewPath)) {
            Response::error("View [{$view}] not found.", 500);
        }

        // Make $vars variables available in both view and layout scopes
        extract($vars, EXTR_SKIP);

        // Capture the view's output
        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        // Render the layout (has access to $content and extracted vars)
        require BASE_PATH . '/app/Views/layouts/main.php';

        exit;
    }
}
