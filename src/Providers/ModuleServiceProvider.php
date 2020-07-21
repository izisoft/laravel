<?php
namespace Izi\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider;

class ModuleServiceProvider extends RouteServiceProvider{

    protected $namespace = 'App\Modules\Frontend\Controllers';

    protected $mapWhat = 'frontend';

    protected $_route = [];
    
    public function register(){
        // echo __METHOD__. '</br>';
    }

    public function boot(){
        
        $this->setNamespace();

        parent::boot();        
    }     

    /**
     * Override the map() function of Illuminate\Foundation\Support\Providers\RouteServiceProvider
     * it will be call by loadRoutes() function
     *
     * @return void
     */
    public function map(){
        $modules = config('modules');

        $method = "map" . $modules[$this->mapWhat]['folder'];
        if(method_exists($this, $method)){
            $this->$method($modules[$this->mapWhat]);
        }else{
            $this->mapModule($modules[$this->mapWhat]);
        }        
    }

    /**
     * Set the corresponding namspace based on the prefix url
     *
     * @return void
     */
    private function setNamespace(){

        $modules = config('modules');

        $isBreak = false;

        //dd(request()->getRouteResolver());

        foreach ($modules as $name => $module) {
            if(isset($module['prefix_url']) && $module['prefix_url'] != "")
            {
                if(request()->is($module['prefix_url']) || request()->is($module['prefix_url'] . '/*')){
                    $this->namespace = join('\\', ['App', 'Modules', $module['folder'], 'Controllers']);
                    $this->mapWhat = $name;
                    $isBreak = true;                    
                } 
            } 
            if($isBreak) break;
        }
         
    }

    /**
     * Mapping module routes and views
     *
     * @param array $mod
     * @return void
     */

    protected function mapModule(array $mod){
        $view_dir = implode(DIRECTORY_SEPARATOR, [base_path('app\Modules'),  $mod['folder'], 'Views']);
        $route_file = implode(DIRECTORY_SEPARATOR, [base_path('app\Modules'),  $mod['folder'], 'Routes', 'web.php']);


        $middleware = ['web'];
        if(is_array($mod['group_middleware']) && !empty($mod['group_middleware'])){
            $middleware = array_merge($middleware, $mod['group_middleware']);
        }
        
        Route::middleware($middleware)
            ->prefix($mod['prefix_url'])
            ->namespace($this->namespace)
            ->group($route_file);
        
        if(is_dir($view_dir)){
            $this->loadViewsFrom($view_dir, $mod['folder']);
        }
    }
 
   /**
    * Mapping api route files
    *
    * @param array $mod
    * @return void
    */
    protected function mapApi(array $mod){
        $route_dir = implode(DIRECTORY_SEPARATOR, [base_path('app\Modules'), $mod['folder'], 'Routes']);
        $entries = scandir($route_dir);
        foreach($entries as $f){
            if($f == '.' || $f == '..')
                continue;
            
            $route_file = implode(DIRECTORY_SEPARATOR, [base_path('app\Modules'), $mod['folder'], 'Routes', $f]);

            $version = pathinfo($f, PATHINFO_FILENAME);            
            
            $middleware = [];
            if(is_array($mod['group_middleware']) && !empty($mod['group_middleware'])){
                $middleware = array_merge($middleware, $mod['group_middleware']);
            }

            /**
            * app/v1
            * app/v2
            * ...
            */

            Route::prefix($mod['prefix_url'] . '/' . $version)
                ->middleware($middleware)
                ->namespace($this->namespace . '\\' . $version)
                ->group($route_file);
        }
    }

}   
