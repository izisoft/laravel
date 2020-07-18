<?php
namespace Izi\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider;

class ModuleServiceProvider extends RouteServiceProvider{

    protected $namespace = 'App\Modules\Frontend\Controllers';

    protected $mapWhat = 'frontend';

    protected $_route = [];
    
    public function register(){}

    public function boot(){

    	/**
         * Define $_server
         */
    	if(!empty($info = $this->getServerInfo()))
        foreach($info as $k=>$v){
            defined($k) or define($k,$v);
        }

        if(!(defined('SERVER_PROTOCOL') && !SERVER_PROTOCOL)){
	        // get shop by domain
	        $shop = $this->getShopInfo();
	        $DOMAIN_INVISIBLED = false;
	        $domain_module = false;
	        $domain_module_name = '';
	        if(!empty($shop)){
	        	define ('SHOP_STATUS',$shop->status);
	            define ('__SID__',$shop->sid);
	            define ('DOMAIN_TEMPLATE',$shop->temp_id);
	            define ('__SITE_NAME__',$shop->code);
	         //    define ('__TEMPLATE_DOMAIN_STATUS__',$shop['state']);
	            define ('DOMAIN_LANGUAGE',$shop->lang);
	         //    define ('DOMAIN_LANGUAGE',!in_array($shop['lang'], ['', 'auto']) ? $shop['lang'] : '');

	            if($shop->module != ""){
	                $this->_route['module'] = $domain_module_name = $shop->module;
	                $domain_module = true;
	                
	            }
	            
	            $DOMAIN_INVISIBLED = isset($shop->is_hidden) && $shop->is_hidden == 1 ? true : false;

	        }else{
	        	define ('SHOP_STATUS',0);
	            define ('__SID__',0);
	            define ('DOMAIN_LAYOUT', 'main');
	            define ('__TEMPLATE_DOMAIN_STATUS__',1);
	            define ('SHOP_CATEGORY', 0);
	        }
	        //

	        defined('DOMAIN_HIDDEN') or define('DOMAIN_HIDDEN', $DOMAIN_INVISIBLED);
	        defined('__DOMAIN_MODULE__') or define('__DOMAIN_MODULE__', $domain_module);
	        defined('__DOMAIN_MODULE_NAME__') or define('__DOMAIN_MODULE_NAME__', $domain_module_name);
    	}
        //
        $this->setNamespace();

        parent::boot();        
    }

    /**
    *	Prepare domain
    *
    */
    /**
     * Parse $_SERVER
     * @return string[]|unknown[]|mixed[]|NULL[]
     */
    
    protected function getServerInfo(){
        $s = $_SERVER;        
        
        $ssl = false;
        
        if(isset($s['HTTPS']) && $s['HTTPS'] == 'on'){
            $ssl = true;
        }elseif(isset($s['HTTP_X_FORWARDED_PROTO']) && strtolower($s['HTTP_X_FORWARDED_PROTO']) == 'https'){
            $ssl = true;
        }else{
            $ssl = isset($s['SERVER_PORT']) && $s['SERVER_PORT'] == 443;
        }
        
         

        $sp = isset($s['SERVER_PROTOCOL']) ? strtolower($s['SERVER_PROTOCOL']) : false;

        if(!$sp) {
        	define('SERVER_PROTOCOL', false);
        	return;
        }

        $protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
        
        $SERVER_PORT = isset($s['SERVER_PORT']) ? $s['SERVER_PORT'] : 80;
        
        $port = $SERVER_PORT;
        $port = in_array($SERVER_PORT , ['80','443']) ? '' : ':'.$port;
        
        
        $host = isset($s['HTTP_X_FORWARDED_HOST']) ? $s['HTTP_X_FORWARDED_HOST'] : (isset($s['HTTP_HOST']) ? $s['HTTP_HOST'] : $s['SERVER_NAME']);
        $path = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : (isset($_SERVER['HTTP_X_ORIGINAL_URL']) ? $_SERVER['HTTP_X_ORIGINAL_URL'] : $_SERVER['QUERY_STRING']);
        $url = $protocol . '://' . $host . $port . $path;
        $pattern = ['/index\.php\//','/index\.php/'];
        $replacement = ['',''];
        $url = preg_replace($pattern, $replacement, $url);
        $a = parse_url($url);
        $a['host'] = preg_replace('/:\d+$/','', strtolower($a['host']));
        return [
            'FULL_URL'=>$url,
            'URL_NO_PARAM'=> $a['scheme'].'://'.$a['host'].$port.$a['path'],
            'URL_WITH_PATH'=>$a['scheme'].'://'.$a['host'].$port.$a['path'],
            'URL_NOT_SCHEME'=>$a['host'].$port.$a['path'],
            'ABSOLUTE_DOMAIN'=>$a['scheme'].'://'.$a['host'],
            'URL_QUERY'=>isset($a['query']) ? $a['query'] : '',
            'DYNAMIC_SCHEME_DOMAIN'  =>  '//'.$a['host'].$port,
            'SCHEME'=>$a['scheme'],
            'DOMAIN'=>$a['host'],
            "__DOMAIN__"=>$a['host'],
            'DOMAIN_NOT_WWW'=>preg_replace('/www./i','',$a['host'],1),
            'URL_NON_WWW'=>preg_replace('/www./i','',$a['host'],1),
            'URL_PORT'=>$port,
            'URL_PATH'=>$a['path'],
            '__TIME__'=>time(),
            'DS' => '/',
            'ROOT_USER'=>'root',
            'ADMIN_USER'=>'admin',
            'DEV_USER'=>'dev',
            'DEMO_USER'=>'demo',
            'USER'=>'user'
        ];
    }
    
    /**
	* Get shop info from domain
	*
    */
	public function getShopInfo()
	{
		return \DB::table('domain_configs as a')
            ->join('shops as b', 'a.sid', '=', 'b.id')    
            ->where(['a.domain'=>__DOMAIN__])        
            ->select([
                'a.sid',
                'a.is_hidden',
                'b.status',
                'b.code',
                'a.module',
                'a.temp_id',              
                'a.lang',
                'a.domain',
            ])
            ->first();
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