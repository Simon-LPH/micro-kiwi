# micro-kiwi
超轻量级PHP开发框架，适用于轻业务多模块的快速开发

# Nginx Config

server {
    listen      80;
    server_name    localhost;
    root  "/home/micro-kiwi";
    location / {
        index  index.html index.htm index.php;
  
        if (!-e $request_filename) {
          rewrite ^/index.php/(.*)$ /index.php?s=$1 last;
          rewrite ^/(.*)$ /index.php?s=$1 last;
          break;
        }
    }
    
    location ~ \.php$ {
      fastcgi_pass   127.0.0.1:9000;
        fastcgi_index   index.php;
        fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
        include        fastcgi_params;
    }
}
