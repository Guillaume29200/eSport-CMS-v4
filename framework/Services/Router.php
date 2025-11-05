<?php
declare(strict_types=1);

namespace Framework\Services;

/**
 * Service Router - Gestion des routes
 * 
 * Support:
 * - Routes GET/POST/PUT/DELETE
 * - Paramètres dynamiques ({id}, {slug})
 * - Middlewares (auth, csrf, rate limit)
 * - Groupes de routes
 */
class Router
{
    private array $routes = [];
    private array $middlewares = [];
    private ?string $currentGroup = null;
    
    /**
     * Enregistrer route GET
     */
    public function get(string $path, $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }
    
    /**
     * Enregistrer route POST
     */
    public function post(string $path, $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }
    
    /**
     * Enregistrer route PUT
     */
    public function put(string $path, $handler, array $middlewares = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middlewares);
    }
    
    /**
     * Enregistrer route DELETE
     */
    public function delete(string $path, $handler, array $middlewares = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middlewares);
    }
    
    /**
     * Ajouter une route
     */
    private function addRoute(string $method, string $path, $handler, array $middlewares = []): void
    {
        // Ajouter préfixe du groupe si applicable
        if ($this->currentGroup) {
            $path = $this->currentGroup . $path;
        }
        
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middlewares' => $middlewares,
            'pattern' => $this->compilePattern($path),
        ];
    }
    
    /**
     * Groupe de routes avec préfixe
     */
    public function group(string $prefix, callable $callback): void
    {
        $previousGroup = $this->currentGroup;
        $this->currentGroup = $prefix;
        
        $callback($this);
        
        $this->currentGroup = $previousGroup;
    }
    
    /**
     * Compiler pattern de route (support {param})
     */
    private function compilePattern(string $path): string
    {
        // Remplacer {param} par regex
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }
    
    /**
     * Dispatcher la requête
     */
    public function dispatch(string $method, string $uri): mixed
    {
        // Nettoyer URI
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';
        
        // Chercher route correspondante
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            if (preg_match($route['pattern'], $uri, $matches)) {
                // Extraire params
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                // Exécuter middlewares
                foreach ($route['middlewares'] as $middleware) {
                    $result = $this->executeMiddleware($middleware);
                    if ($result !== true) {
                        return $result; // Middleware a bloqué
                    }
                }
                
                // Exécuter handler
                return $this->executeHandler($route['handler'], $params);
            }
        }
        
        // Route non trouvée
        http_response_code(404);
        throw new RouterException('Route not found: ' . $uri);
    }
    
    /**
     * Exécuter middleware
     */
    private function executeMiddleware($middleware): mixed
    {
        if (is_callable($middleware)) {
            return $middleware();
        }
        
        if (is_string($middleware) && class_exists($middleware)) {
            $instance = new $middleware();
            if (method_exists($instance, 'handle')) {
                return $instance->handle();
            }
        }
        
        throw new RouterException('Invalid middleware');
    }
    
    /**
     * Exécuter handler
     */
    private function executeHandler($handler, array $params): mixed
    {
        // Callable
        if (is_callable($handler)) {
            return $handler($params);
        }
        
        // Format "ControllerClass@method"
        if (is_string($handler) && str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler);
            
            if (!class_exists($class)) {
                throw new RouterException("Controller not found: {$class}");
            }
            
            $controller = new $class();
            
            if (!method_exists($controller, $method)) {
                throw new RouterException("Method not found: {$class}@{$method}");
            }
            
            return $controller->$method($params);
        }
        
        throw new RouterException('Invalid handler');
    }
    
    /**
     * Générer URL depuis nom de route
     */
    public function url(string $name, array $params = []): string
    {
        // TODO: Implémenter named routes
        return '';
    }
    
    /**
     * Redirection
     */
    public function redirect(string $url, int $code = 302): void
    {
        header("Location: {$url}", true, $code);
        exit;
    }
}

/**
 * Exception Router
 */
class RouterException extends \Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message, 404);
    }
}
