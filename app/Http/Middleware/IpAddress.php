<?php

namespace Horsefly\Http\Middleware;

use Closure;

class IpAddress
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        //$ip_address = $request->ip();
        //$ip_addresses = \Horsefly\IpAddress::where('status', 'active')->select('ip_address')->get()->pluck('ip_address')->toArray();
		//$openVox = in_array('milkyway.tranzcript.com', $ip_addresses);
        //$ip = gethostbyname('milkyway.tranzcript.com');
        //$ip_addresses = str_replace('milkyway.tranzcript.com', $ip, $ip_addresses);
        //return ( in_array($ip_address, $ip_addresses) || preg_match("/^(192)\.(168)\.(110)\.\d{1,3}/", $ip_address) )? $next($request) :          redirect()->to('http://www.ibstec.com/');
		
		   //temprory ip address wildcard add
		
		  $originalIp = $request->ip();
        $newLastDigit = "*"; // Replace with the desired wildcard character

// Explode the original IP address into an array of octets
        $octets = explode('.', $originalIp);

// Replace the last octet with the wildcard
        $octets[3] = $newLastDigit;

// Join the modified octets back into an IP address
        $modifiedIp = implode('.', $octets);

// Get the IP address associated with the hostname 'milkyway.tranzcript.com'
        $ip = gethostbyname('milkyway.tranzcript.com');


// Replace the hostname in the database IP addresses with the resolved IP
        $ip_addresses_db = \Horsefly\IpAddress::where('status', 'active')->select('ip_address')->get()->pluck('ip_address')->toArray();
        $ip_addresses = str_replace('milkyway.tranzcript.com', $ip, $ip_addresses_db);

// Modify each IP address from the database similarly
        $modifiedIp_db = array_map(function ($ip) use ($newLastDigit) {
            $octets = explode('.', $ip);
            $octets[3] = $newLastDigit;
            return implode('.', $octets);
        }, $ip_addresses);

        return ($modifiedIp_db)? $next($request) :
            redirect()->to('http://www.ibstec.com/');

      
    }
}