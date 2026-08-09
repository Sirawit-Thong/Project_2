<?php
/**
 * Router
 * จัดการเส้นทาง URL → Controller@method
 */

class Router
{
    private $routes = [];

    /**
     * ลงทะเบียน route
     */
    public function get($path, $handler)
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post($path, $handler)
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function match($method, $path, $handler)
    {
        $this->routes[strtoupper($method)][$path] = $handler;
    }

    /**
     * ค้นหา route ที่ตรงกับ request
     * คืนค่า [handler, params] หรือ null
     */
    public function dispatch($method, $uri)
    {
        $method = strtoupper($method);
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';

        // ลองจับคู่ exact match ก่อน
        if (isset($this->routes[$method][$uri])) {
            return ['handler' => $this->routes[$method][$uri], 'params' => []];
        }

        // ลองจับคู่ parameterized route (เช่น /equipment/{id})
        foreach ($this->routes[$method] ?? [] as $pattern => $handler) {
            $params = $this->matchPattern($pattern, $uri);
            if ($params !== false) {
                return ['handler' => $handler, 'params' => $params];
            }
        }

        return null;
    }

    /**
     * จับคู่ pattern กับ URI (รองรับ {param})
     */
    private function matchPattern($pattern, $uri)
    {
        $pattern = rtrim($pattern, '/') ?: '/';
        $patternParts = explode('/', trim($pattern, '/'));
        $uriParts = explode('/', trim($uri, '/'));

        if (count($patternParts) !== count($uriParts)) {
            return false;
        }

        $params = [];
        foreach ($patternParts as $i => $part) {
            if (preg_match('/^\{(\w+)\}$/', $part, $m)) {
                $params[$m[1]] = $uriParts[$i];
            } elseif ($part !== $uriParts[$i]) {
                return false;
            }
        }

        return $params;
    }

    /**
     * เรียก handler (@Controller@method หรือ closure)
     */
    public function callHandler($handler, $params)
    {
        if (is_array($handler)) {
            [$controllerName, $method] = $handler;
            $controller = new $controllerName();
            return call_user_func_array([$controller, $method], $params);
        }

        if (is_string($handler)) {
            // Format: "Controller@method"
            [$controllerName, $method] = explode('@', $handler);
            $controller = new $controllerName();
            return call_user_func_array([$controller, $method], $params);
        }

        if (is_callable($handler)) {
            return call_user_func_array($handler, array_values($params));
        }
    }
}
