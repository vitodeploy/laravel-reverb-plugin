#[laravel-reverb]
handle_path /app/* /apps/* {
    reverse_proxy 0.0.0.0:{{ $port }} {
        header_up Host {host}
        header_up X-Forwarded-For {remote_host}
        header_up X-Forwarded-Proto {scheme}
        header_up X-Forwarded-Port {server_port}
        header_up Upgrade {http.upgrade}
        header_up Connection {http.connection}
    }
}
#[/laravel-reverb]

