<?php namespace albreis\cms\controllers;

use CMS;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Excel;
use Illuminate\Support\Facades\PDF;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;

class ApiCustomController extends CMSController
{
    public function cmsInit()
    {
        $this->table = 'cms_apicustom';
        $this->primary_key = 'id';
        $this->title_field = "nama";
        $this->button_show = false;
        $this->button_new = false;
        $this->button_delete = false;
        $this->button_add = false;
        $this->button_import = false;
        $this->button_export = false;
    }

    function getIndex()
    {
        $this->cbLoader();

        if (! CMS::isSuperadmin()) {
            CMS::insertLog(cbLang("log_try_view", ['name' => 'API Index', 'module' => 'API']));
            CMS::redirect(CMS::adminPath(), cbLang('denied_access'));
        }

        $data = [];

        $data['page_title'] = 'API Generator';
        $data['page_menu'] = Route::getCurrentRoute()->getActionName();
        $data['apis'] = DB::table('cms_apicustom')->orderby('nama', 'asc')->get();

        return view('cms::api_documentation', $data);
    }

    function apiDocumentation()
    {
        $this->cbLoader();
        $data = [];

        $data['apis'] = DB::table('cms_apicustom')->orderby('nama', 'asc')->get();

        return view('cms::api_documentation_public', $data);
    }

    function getDownloadPostman()
    {
        $this->cbLoader();
        $data = [];
        $data['variables'] = [];
        $data['info'] = [
            'name' => CMS::getSetting('appname').' - API',
            '_postman_id' => "1765dd11-73d1-2978-ae11-36921dc6263d",
            'description' => '',
            'schema' => 'https://schema.getpostman.com/json/collection/v2.0.0/collection.json',
        ];
        $items = [];
        $apis = DB::table('cms_apicustom')->orderby('nama', 'asc')->get();

        foreach ($apis as $a) {
            $parameters = unserialize($a->parameters);
            $formdata = [];
            $httpbuilder = [];
            if ($parameters) {
                foreach ($parameters as $p) {
                    $enabled = ($p['used'] == 0) ? false : true;
                    $name = $p['name'];
                    $httpbuilder[$name] = '';
                    if ($enabled) {
                        $formdata[] = ['key' => $name, 'value' => '', 'type' => 'text', 'enabled' => $enabled];
                    }
                }
            }

            if (strtolower($a->method_type) == 'get') {
                if ($httpbuilder) {
                    $httpbuilder = "?".http_build_query($httpbuilder);
                } else {
                    $httpbuilder = '';
                }
            } else {
                $httpbuilder = '';
            }

            $items[] = [
                'name' => $a->nama,
                'request' => [
                    'url' => url('api/'.$a->permalink).$httpbuilder,
                    'method' => $a->method_type ?: 'GET',
                    'header' => [],
                    'body' => [
                        'mode' => 'formdata',
                        'formdata' => $formdata,
                    ],
                    'description' => $a->keterangan,
                ],
            ];
        }
        $data['item'] = $items;

        $json = json_encode($data);

        return \Response::make($json, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename='.CMS::getSetting('appname').' - API For POSTMAN.json',
        ]);
    }

    public function getScreetKey()
    {
        $this->cbLoader();
        $data['page_title'] = 'API Generator';
        $data['page_menu'] = Route::getCurrentRoute()->getActionName();
        $data['apikeys'] = DB::table('cms_apikey')->get();

        return view('cms::api_key', $data);
    }

    public function getGenerator()
    {
        $this->cbLoader();

        if (! CMS::isSuperadmin()) {
            CMS::insertLog(cbLang("log_try_view", ['name' => 'API Index', 'module' => 'API']));
            CMS::redirect(CMS::adminPath(), cbLang('denied_access'));
        }

        $data['page_title'] = 'API Generator';
        $data['page_menu'] = Route::getCurrentRoute()->getActionName();

        $tables = CMS::listTables();
        $tables_list = [];
        foreach ($tables as $tab) {
            foreach ($tab as $key => $value) {
                $tables_list[] = $value;
            }
        }

        $data['tables'] = $tables_list;

        return view('cms::api_generator', $data);
    }

    public function getEditApi($id)
    {
        $this->cbLoader();

        if (! CMS::isSuperadmin()) {
            CMS::insertLog(cbLang("log_try_view", ['name' => 'API Edit', 'module' => 'API']));
            CMS::redirect(CMS::adminPath(), cbLang('denied_access'));
        }

        $row = DB::table('cms_apicustom')->where('id', $id)->first();

        $data['row'] = $row;
        $data['parameters'] = json_encode(unserialize($row->parameters));
        $data['responses'] = json_encode(unserialize($row->responses));
        $data['page_title'] = 'API Generator';
        $data['page_menu'] = Route::getCurrentRoute()->getActionName();

        $tables = CMS::listTables();
        $tables_list = [];
        foreach ($tables as $tab) {
            foreach ($tab as $key => $value) {
                $tables_list[] = $value;
            }
        }

        $data['tables'] = $tables_list;

        return view('cms::api_generator', $data);
    }

    function getGenerateScreetKey()
    {
        $this->cbLoader();
        //Generate a random string.
        $token = openssl_random_pseudo_bytes(16);

        //Convert the binary data into hexadecimal representation.
        $token = bin2hex($token);

        $id = DB::table('cms_apikey')->insertGetId([
            'screetkey' => $token,
            'created_at' => date('Y-m-d H:i:s'),
            'status' => 'active',
            'hit' => 0,
        ]);

        $response = [];
        $response['key'] = $token;
        $response['id'] = $id;

        return response()->json($response);
    }

    public function getStatusApikey()
    {
        CMS::valid(['id', 'status'], 'view');

        $id = Request::get('id');
        $status = (Request::get('status') == 1) ? "active" : "non active";

        DB::table('cms_apikey')->where('id', $id)->update(['status' => $status]);

        return redirect()->back()->with(['message' => 'You have been update api key status !', 'message_type' => 'success']);
    }

    public function getDeleteApiKey()
    {

        $id = Request::get('id');
        if (DB::table('cms_apikey')->where('id', $id)->delete()) {
            return response()->json(['status' => 1]);
        } else {
            return response()->json(['status' => 0]);
        }
    }

    function getColumnTable($table, $type = 'list')
    {
        $this->cbLoader();
        $result = [];

        $cols = CMS::getTableColumns($table);

        $except = ['created_at', 'deleted_at', 'updated_at'];

        $result = $cols;
        $new_result = [];
        foreach ($result as $ro) {

            if (in_array($ro, $except)) {
                continue;
            }

            $type_field = CMS::getFieldType($table, $ro);

            $type_field = (array_search($ro, explode(',', config('cms.EMAIL_FIELDS_CANDIDATE'))) !== false) ? "email" : $type_field;
            $type_field = (array_search($ro, explode(',', config('cms.IMAGE_FIELDS_CANDIDATE'))) !== false) ? "image" : $type_field;
            $type_field = (array_search($ro, explode(',', config('cms.PASSWORD_FIELDS_CANDIDATE'))) !== false) ? "password" : $type_field;

            $type_field = (substr($ro, -3) == '_id') ? "integer" : $type_field;
            $type_field = (substr($ro, 0, 3) == 'id_') ? "integer" : $type_field;

            $new_result[] = ['name' => $ro, 'type' => $type_field];

            if ($type == 'list' || $type == 'detail') {
                if (substr($ro, 0, 3) == 'id_') {
                    $table2 = substr($ro, 3);
                    $t2 = DB::getSchemaBuilder()->getColumnListing($table2);
                    foreach ($t2 as $t) {
                        if ($t != 'id' && $t != 'created_at' && $t != 'updated_at' && $t != 'deleted_at') {

                            if (substr($t, 0, 3) == 'id_') {
                                continue;
                            }

                            $type_field = CMS::getFieldType($table2, $t);
                            $t = str_replace("_$table2", "", $t);
                            $new_result[] = ['name' => $table2.'_'.$t, 'type' => $type_field];
                        }
                    }
                }
            }
        }

        return response()->json($new_result);
    }

    function postSaveApiCustom()
    {
        $this->cbLoader();
        $posts = Request::all();

        $a = [];

        $a['nama'] = g('nama');
        $a['tabel'] = $posts['tabel'];
        $a['aksi'] = $posts['aksi'];
        $a['permalink'] = g('permalink');
        $a['method_type'] = g('method_type');
        $a['controller'] = g('controller');

        $params_name = g('params_name');
        $params_type = g('params_type');
        $params_config = g('params_config');
        $params_required = g('params_required');
        $params_used = g('params_used');
        $json = [];

        for ($i = 0; $i <= count($params_name); $i++) {
            if ($params_name[$i]) {
                $json[] = [
                    'name' => $params_name[$i],
                    'type' => $params_type[$i],
                    'config' => $params_config[$i],
                    'required' => $params_required[$i],
                    'used' => $params_used[$i],
                ];
            }
        }

        $json = array_filter($json);
        $a['parameters'] = serialize($json);

        $a['sql_where'] = g('sql_where');

        $responses_name = g('responses_name');
        $responses_type = g('responses_type');
        $responses_subquery = g('responses_subquery');
        $responses_used = g('responses_used');
        $json = [];
        for ($i = 0; $i <= count($responses_name); $i++) {
            if ($responses_name[$i]) {
                $json[] = [
                    'name' => $responses_name[$i],
                    'type' => $responses_type[$i],
                    'subquery' => $responses_subquery[$i],
                    'used' => $responses_used[$i],
                ];
            }
        }

        $json = array_filter($json);
        $a['responses'] = serialize($json);
        $a['keterangan'] = g('keterangan');

        if (Request::get('id')) {

            $old = DB::table('cms_apicustom')->find(Request::get('id'));
                
            $oldControllerName = ucwords(str_replace('/', ' ', $old->permalink));
            $oldControllerName = str_replace(' ', '/', $oldControllerName);
            $oldControllerName = ucwords(str_replace('_', ' ', $oldControllerName));
            $oldControllerName = str_replace(' ', '', $oldControllerName).'Controller'.ucwords($old->method_type);

            if($a['controller']) {
                $controllerName = $a['controller'];
            }
            else {
                $controllerName = ucwords(str_replace('/', ' ', $a['permalink']));
                $controllerName = str_replace(' ', '/', $controllerName);
                $controllerName = ucwords(str_replace('_', ' ', $controllerName));
                $controllerName = str_replace(' ', '', $controllerName).'Controller'.ucwords($a['method_type']);
            }

            $controllerName = preg_replace('/[^\/\w\d]+/', '', $controllerName);

            if(!file_exists(dirname(app_path("Http/Controllers/Api/{$controllerName}.php")))) {
                mkdir(dirname(app_path("Http/Controllers/Api/{$controllerName}.php")), 0775, true);
            }

            if("Http/Controllers/Api/{$oldControllerName}.php" != "Http/Controllers/Api/{$controllerName}.php") {
                move_uploaded_file(app_path("Http/Controllers/Api/{$oldControllerName}.php"), app_path("Http/Controllers/Api/{$controllerName}.php"));
            }

            DB::table('cms_apicustom')->where('id', g('id'))->update($a);
            if(!file_exists(app_path("Http/Controllers/Api/{$controllerName}.php"))) {
                CMS::generateAPI($controllerName, $a['tabel'], $a['permalink'], $a['method_type']);
            }
        } else {

            if($a['controller']) {
                $controllerName = $a['controller'].'Controller'.ucwords($a['method_type']);
            }
            else {
                $controllerName = ucwords(str_replace('/', ' ', $a['permalink']));
                $controllerName = str_replace(' ', '/', $controllerName);
                $controllerName = ucwords(str_replace('_', ' ', $controllerName));
                $controllerName = str_replace(' ', '', $controllerName).'Controller'.ucwords($a['method_type']);
                $controllerName = preg_replace('/[^\/\w\d]+/', '', $controllerName);
            }
            $a['controller'] = $controllerName;
            CMS::generateAPI($controllerName, $a['tabel'], $a['permalink'], $a['method_type']);


            DB::table('cms_apicustom')->insert($a);
        }

        $routes = DB::table('cms_apicustom')->limit(100)->get();  
        $apis = [];
        foreach($routes as $route) {
            if(empty($route->controller)) continue;
            $cname = str_replace('/', '\\', $route->controller);
            $route->permalink = preg_replace('/\[([^\/]+)\]/', '$1', $route->permalink);
            $apis[] = "Route::{$route->method_type}('api/{$route->permalink}', '{$cname}@execute_api');";
        }

        file_put_contents(base_path('routes/apicustom.php'), "<?php\n" . implode("\n", $apis));

        return redirect(CMS::mainpath())->with(['message' => 'Yeay, your api has been saved successfully !', 'message_type' => 'success']);
    }

    function getDeleteApi($id)
    {
        $this->cbLoader();
        $row = DB::table('cms_apicustom')->where('id', $id)->first();
        DB::table('cms_apicustom')->where('id', $id)->delete();
        
        @unlink(app_path("Http/Controllers/Api/{$row->controller}.php"));
        
        $routes = DB::table('cms_apicustom')->limit(100)->get();  
        $apis = [];
        foreach($routes as $route) {
            if(empty($route->controller)) continue;
            $cname = str_replace('/', '\\', $route->controller);
            $route->permalink = preg_replace('/\[([^\/]+)\]/', '$1', $route->permalink);
            $apis[] = "Route::{$route->method_type}('api/{$route->permalink}', '{$cname}@execute_api');";
        }

        file_put_contents(base_path('routes/apicustom.php'), "<?php\n" . implode("\n", $apis));

        return response()->json(['status' => 1]);
    }
}
