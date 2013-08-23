<?php

/**
 * Step 1: Require the Slim PHP 5 Framework
 *
 * If using the default file layout, the `Slim/` directory
 * will already be on your include path. If you move the `Slim/`
 * directory elsewhere, ensure that it is added to your include path
 * or update this file path as needed.
 */
require 'Slim/Slim.php';

/**
 * Step 2: Instantiate the Slim application
 *
 * Here we instantiate the Slim application with its default settings.
 * However, we could also pass a key-value array of settings.
 * Refer to the online documentation for available settings.
 */
$app = new Slim();

/**
 * Step 3: Define the Slim application routes
 *
 * Here we define several Slim application routes that respond
 * to appropriate HTTP request methods. In this example, the second
 * argument for `Slim::get`, `Slim::post`, `Slim::put`, and `Slim::delete`
 * is an anonymous function. If you are using PHP < 5.3, the
 * second argument should be any variable that returns `true` for
 * `is_callable()`. An example GET route for PHP < 5.3 is:
 *
 * $app = new Slim();
 * $app->get('/hello/:name', 'myFunction');
 * function myFunction($name) { echo "Hello, $name"; }
 *
 * The routes below work with PHP >= 5.3.
 */

//Enable CORS
$app->response->headers->set('Access-Control-Allow-Origin', '*');

//GET route
$app->get('/', function () {
    $template = <<<EOT
<!DOCTYPE html>
    <html>
        <head>
            <meta charset="utf-8"/>
            <title>Ziptastic API</title>
            <style>
                html,body,div,span,object,iframe,
                h1,h2,h3,h4,h5,h6,p,blockquote,pre,
                abbr,address,cite,code,
                del,dfn,em,img,ins,kbd,q,samp,
                small,strong,sub,sup,var,
                b,i,
                dl,dt,dd,ol,ul,li,
                fieldset,form,label,legend,
                table,caption,tbody,tfoot,thead,tr,th,td,
                article,aside,canvas,details,figcaption,figure,
                footer,header,hgroup,menu,nav,section,summary,
                time,mark,audio,video{margin:0;padding:0;border:0;outline:0;font-size:100%;vertical-align:baseline;background:transparent;}
                body{line-height:1;}
                article,aside,details,figcaption,figure,
                footer,header,hgroup,menu,nav,section{display:block;}
                nav ul{list-style:none;}
                blockquote,q{quotes:none;}
                blockquote:before,blockquote:after,
                q:before,q:after{content:'';content:none;}
                a{margin:0;padding:0;font-size:100%;vertical-align:baseline;background:transparent;}
                ins{background-color:#ff9;color:#000;text-decoration:none;}
                mark{background-color:#ff9;color:#000;font-style:italic;font-weight:bold;}
                del{text-decoration:line-through;}
                abbr[title],dfn[title]{border-bottom:1px dotted;cursor:help;}
                table{border-collapse:collapse;border-spacing:0;}
                hr{display:block;height:1px;border:0;border-top:1px solid #cccccc;margin:1em 0;padding:0;}
                input,select{vertical-align:middle;}
                html{ background: #EDEDED; height: 100%; }
                body{background:#FFF;margin:0 auto;min-height:100%;padding:0 30px;width:440px;color:#666;font:14px/23px Arial,Verdana,sans-serif;}
                h1,h2,h3,p,ul,ol,form,section{margin:0 0 20px 0;}
                h1{color:#333;font-size:20px;}
                h2,h3{color:#333;font-size:14px;}
                h3{margin:0;font-size:12px;font-weight:bold;}
                ul,ol{list-style-position:inside;color:#999;}
                ul{list-style-type:square;}
                code,kbd{background:#EEE;border:1px solid #DDD;border:1px solid #DDD;border-radius:4px;-moz-border-radius:4px;-webkit-border-radius:4px;padding:0 4px;color:#666;font-size:12px;}
                pre{background:#EEE;border:1px solid #DDD;border-radius:4px;-moz-border-radius:4px;-webkit-border-radius:4px;padding:5px 10px;color:#666;font-size:12px;}
                pre code{background:transparent;border:none;padding:0;}
                a{color:#70a23e;}
                header{padding: 30px 0;text-align:center;}
            </style>
        </head>
        <body>
        <a href="https://github.com/joshstrange/Ziptastic"><img style="position: absolute; top: 0; right: 0; border: 0;" src="https://a248.e.akamai.net/assets.github.com/img/71eeaab9d563c2b3c590319b398dd35683265e85/687474703a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f72696768745f677261795f3664366436642e706e67" alt="Fork me on GitHub"></a>
            <header>
                <a href="http://www.slimframework.com">Built With <img src="logo.png" alt="Slim"/></a>
            </header>
            <h1>Ziptastic API</h1>
            <p>
                Ziptastic API is a super easy to use API that returns the Country, State, City of the ip code you supply. The Ziptastic API was created by <a href="http://github.com/daspecster">Thomas Schultz</a> and then I (<a href="http://github.com/joshstrange">Josh Strange</a>) re-wrote parts of it in PHP using the <a href="http://www.slimframework.com/">SLIM framework</a> and hosted it here.
            </p>
            <section>
                <h2>Get Started</h2>
                <ol>
                    <li>Step 1: Make an API call to  http://ZiptasticAPI.com/ZIPCODE OR http://ZiptasticAPI.com/ZIPCODE?callback=myCallback</li>
                    <li>Step 2: Get response from server</li>
                    <li>Step 3: ????</li>
                    <li>Step 4: PROFIT!!! (But really it's just that easy)</li>
                </ol>
            </section>
        </body>
    </html>
EOT;
    echo $template;
});

//Zip route
$app->get('/:zip', function ($zip) {
    if(!is_numeric($zip))
    {
        if(isset($_GET['callback']) && !empty($_GET['callback']))
        {
            die($_GET['callback'].'('.json_encode(array('error' => 'Not a valid Zip Code!')).')');
        }
        else
        {
            die(json_encode(array('error' => 'Not a valid Zip Code!')));
        }
        
    }




    try
    {
        //open the database
        $db = new PDO('sqlite:zipcodes.db');

        $result = $db->query("SELECT country,state,city FROM zipcodes WHERE zipcode='$zip' LIMIT 1");
        $info = $result->fetch(PDO::FETCH_ASSOC);
        if(!$info)
        {
            $response = array('error' => 'Zip Code not found!');
            if(isset($_GET['callback']) && !empty($_GET['callback']))
            {
                  die($_GET['callback'].'('.json_encode($response).')');
            }
            else
            {
               die(json_encode($response));
            }

        }
        else
        {
            $db->query("UPDATE zipcodes SET fetches=fetches+1 WHERE zipcode='$zip'");
            if(isset($_GET['callback']) && !empty($_GET['callback']))
            {
                echo $_GET['callback'].'('.json_encode($info).')';
            }
            else
            {
                echo json_encode($info);
            }

        }
        // close the database connection
        $db = NULL;
    }
    catch(PDOException $e)
    {
        print 'Exception : '.$e->getMessage();
    }



    /*
    $db = new SQLiteDatabase('zipcodes.db');
    $result = $db->query("SELECT country,state,city FROM zipcodes WHERE zipcode='$zip' LIMIT 1");

    if($info = sqlite_fetch_array($result))
        die(json_encode(array('error' => 'Zip Code not found!')));
    else
    {
        echo json_encode($info);
    }*/
});


/**
 * Step 4: Run the Slim application
 *
 * This method should be called last. This is responsible for executing
 * the Slim application using the settings and routes defined above.
 */
$app->run();
