First: How to install?

    1.composer require zhyu/packages
    2.php artisan vendor:publish --tag=zhyu --force
    3.php artisan migrate
    4.create fllow config in .env file
        #---super admin to manage resources
        ZHYU_ADMIN_USER_IDS=1
    5.Register alias in config/app.php
        'ZhyuGate' => Zhyu\Facades\ZhyuGate::class,
    6.append these lines in app\Providers\AuthServiceProvider.php boot method
        ZhyuGate::init();

    7.make usergroup model in \App folder





Second: Rules

    1.datatable ajax url parameters rules
        /ajax/{model}-{key}/{limit?}?query=UserName:=:david*UserPhone:=:0233223333
            ?query=parent_id:=:0
            ?query=parent_id:whereNull

Others:
    1.create reoureces route
    /resources

    2.In Contorller you can create these function to validate or filter request
        rules() rules_create() rules_edit() filter() filter_create() filter_edit()



Commands:
    1.repository
        php artisan make:repository {repository} --m={model}

    2.resource
        php artisan make:res {name} {--route=} {--m=}

    3.resource collection
        php artisan make:rcollection {name}

    4.datatable
        php artisan make:datatable {name} {--m=} {--act=} {--resource=}

    5.crud create all in one
        php artisan make:crud AAAController --r={repository} --m={model} --datatable={datatable} --route={route} --resource={resource} --act={act}
        *datatable ajax url: /ajax-{model}-{act}


Facades:
    1.ZhyuCurl
        $data = ZhyuCurl::url($url)->method($method)->json($params);
        $data = ZhyuCurl::url($url)->post($params);
        $data = ZhyuCurl::url($url)->put($params);
        $data = ZhyuCurl::url($url)->patch($params);
        $data = ZhyuCurl::url($url)->delete($params);
        $data = ZhyuCurl::url($url)->get($params);

