<?php
//dec 2...2nd? 2014 - all code in order
//ideas: add a user remover?
//check users every so often for bad stuff like undernet or failing to connect to the BNC channel
//Eliminated Bug Number 0000000001! Woo!
set_time_limit( 0 );

// server connection information
global $servers;
$servers["StormBNC"]["name"]        = "StormBNC";
$servers["StormBNC"]["serverip"]    = "127.0.0.1";
$servers["StormBNC"]["serverport"]  = "4950";
$servers["StormBNC"]["serverpass"]  = "botzncuser:botpass";
$servers["StormBNC"]["botnick"]     = "StormBNC-Registration";
$servers["StormBNC"]["botuser"]     = "botzncuser";
$servers["StormBNC"]["botrealname"] = "StormBNC Registration Bot";
$servers["StormBNC"]["botnspass"]   = "StormBNC-Registration botnickservpassword";
//fancy
$servers["StormBNC"]["channels"][0] = "stormbnc";
$servers["StormBNC"]["autoexec"][0] = "PRIVMSG #stormbnc :StormBNC Registration Bot ONLINE.\r\n"; //doesn't work for some reason

//BNC administrators, by nickname
global $bncadmins;
$bncadmins[0] = "BNCAdministratorNickname";

//permit new account requests or approval/denial
$bncfreeze    = false;

// Multi-delimiter explode.
function explodeX( $delimiters, $string ) {
	$return_array = Array(
		 $string
	); // The array to return
	$d_count      = 0;
	while ( isset( $delimiters[$d_count] ) ) // Loop to loop through all delimiters
		{
		$new_return_array = Array();
		foreach ( $return_array as $el_to_split ) // Explode all returned elements by the next delimiter
			{
			$put_in_new_return_array = explode( $delimiters[$d_count], $el_to_split );
			foreach ( $put_in_new_return_array as $substr ) // Put all the exploded elements in array to return
				{
				$new_return_array[ ] = $substr;
			}
		}
		$return_array = $new_return_array; // Replace the previous return array by the next version
		$d_count++;
	}
	return $return_array; // Return the exploded elements
}
function array_trim( $a ) {
	$j = 0;
	for ( $i = 0; $i < count( $a ); $i++ ) {
		if ( $a[$i] != "" ) {
			$b[$j++] = $a[$i];
		}
	}
	return $b;
}
// Parser
function parseIrcMessage( $message, $server, $socket, $registered ) {
	global $chars;
	$message         = ltrim( $message, ":" );
	$strings         = explode( ":", $message, 2 );
	$exploded_output = explode( " ", $strings[0] );
	if ( array_key_exists( 1, $strings ) ) {
		$exploded_output[ ] = $strings[1];
	}
	$exploded_output       = array_trim( $exploded_output );
	$server_output         = array();
	$server_output["from"] = explodeX( "!@", $exploded_output[0] );
	$server_output["data"] = $exploded_output;
	@consoleout( $server, $server_output["data"][0] . " " . $server_output["data"][1] . " " . $server_output["data"][2] . " " . $server_output["data"][3], $socket, $registered );
	return $server_output;
}
// Function to display console output
function consoleout( $name, $message, $server = NULL, $registered = false, $outgoing = false ) {
	global $servers;
	global $chars;
	$now = date("M d Y, h:i:s");
	if ( $outgoing !== false ) {
		echo ( ">> (" . $now . ") [ConsoleOut:" . $name . "] " . $message . "\n" );
		$fp = fopen( "logs/all.log", "a" );
		fwrite( $fp, ">> (" . $now . ") [ConsoleOut:" . $name . "] " . $message . "\n" );
		fclose( $fp );
		$fp = fopen( "logs/" . preg_replace( "/[^A-Za-z]/", '', strtolower( $name ) ) . ".log", "a" );
		fwrite( $fp, ">> (" . $now . ") [ConsoleOut:" . $name . "] " . $message . "\n" );
		fclose( $fp );
	} else {
		echo ( "<< (" . $now . ") [ConsoleOut:" . $name . "] " . $message . "\n" );
		$fp = fopen( "logs/all.log", "a" );
		fwrite( $fp, "<< (" . $now . ") [ConsoleOut:" . $name . "] " . $message . "\n" );
		fclose( $fp );
		$fp = fopen( "logs/" . preg_replace( "/[^A-Za-z]/", '', strtolower( $name ) ) . ".log", "a" );
		fwrite( $fp, "<< (" . $now . ") [ConsoleOut:" . $name . "] " . $message . "\n" );
		fclose( $fp );
	}
}
function send( $server, $message, $quiet = false )
{
	fwrite( $server["socket"], $message );
	if ( !$quiet ) {
		consoleout( $server["name"], ">> " . trim( $message ), NULL, false, true );
	}
}
//BNC userdata loading stuff
if ( file_exists( "./bncusers.json" ) == true ) {
	//Load user list file
	consoleout( "BNC Module", "BNC unapproved user data found. Loading..." );
	$bncusers = json_decode( file_get_contents( "./bncusers.json" ), true );
	consoleout( "BNC Module", "BNC unapproved user data loaded." );
} else {
	//set a var up, but don't make the file.
	consoleout( "BNC Module", "No BNC unapproved user data was found. Initializing variable." );
	$bncusers = array();
	consoleout( "BNC Module", "Userdata variable initialized." );
}
//BNC mailing script
function bncMail( $bncname, $bncemail, $mailaction, $bncpass = " " )
{
	//sanitized to hell upon request so don't worry
	exec( "./bnc-mail " . $bncname . " " . $bncemail . " " . $mailaction . " " . $bncpass );
	consoleout( "BNC Module", "ALERT! BNC code attempted to mail stuff: './bnc-mail " . $bncname . " " . $bncemail . " " . $mailaction . " " . $bncpass . "'\n" );
}
// Main loop
$first = true;
$server["registered"] = false;
while ( 1 ) {
	foreach ( $servers as &$server ) {
		$server["pinged"] = false;
		if ( $first ) {
			consoleout( $server["name"], "Connecting to " . $server["name"] . " on port " . $server["serverport"] . "..." );
			if ( !( $server["socket"] = fsockopen( $server["serverip"], $server["serverport"], $errno, $errstr, 5 ) ) ) {
				consoleout( $server["name"], "CONNECTION ERROR!" );
			}
			//stream_set_blocking($server["socket"],0);
			consoleout( $server["name"], "Socket established, waiting for server to respond..." );
			$server["registered"] = false;
			//moved since BNC
			if ( !$server["registered"] ) {
				send( $server, "NICK " . $server["botnick"] . "\r\nUSER " . $server["botuser"] . " 0 * :" . $server["botrealname"] . "\r\n" );
				if ( $server["serverpass"] != null ) {
					fwrite( $server["socket"], "PASS " . $server["serverpass"] . "\r\n" );
					//didn't use send(); because it's a password.
				}
				$server["registered"] = true;
				consoleout( $server["name"], "Registered!" );
				if ( !isset( $server["autoexec"] ) ) {
					foreach ( $server["autoexec"] as &$execcmd ) {
						send( $server, $execcmd );
					}
				}
			}
			$server["sockout"] = "";
			$randvar           = 0;
		}
		$server["sockout"] = trim( fgets( $server["socket"] ), "\n" );
		if ( $server["sockout"] != "" ) {
			$server_output = parseIrcMessage( $server["sockout"], $server["name"], $server["socket"], $server["registered"] );
			//print($server["sockout"]."\n"); // Debug for server output.
			//print_r($server_output); // Debug for server output.
			if ( !$server["registered"] ) {
				send( $server, "NICK " . $server["botnick"] . "\r\nUSER " . $server["botuser"] . " 0 * :" . $server["botrealname"] . "\r\n" );
				if ( $server["serverpass"] != null ) {
					send( $server, "PASS " . $server["serverpass"] . "\r\n" );
				}
				$server["registered"] = true;
				consoleout( $server["name"], "Registered!" );
			}
			if ( $server_output["data"][1] == "001" ) {
				send( $server, "PRIVMSG NickServ :IDENTIFY " . $server["botnspass"] . "\r\n" );
				foreach ( $server["channels"] as &$chan ) {
					send( $server, "JOIN #" . $chan . "\r\n" );
				}
				if ( !isset( $server["autoexec"] ) ) {
					foreach ( $server["autoexec"] as &$execcmd ) {
						send( $server, $execcmd );
					}
				}
			}
			if ( $server_output["data"][0] == "PING" ) {
				send( $server, "PONG :" . $server_output["data"][1] . "\r\n", true );
				$server["pinged"] = true;
			}
			if ( $server_output["data"][1] == "KICK" ) {
				if ( $server_output["data"][3] == $server["botnick"] ) {
					sleep( 1 );
					send( $server, "JOIN " . $server_output["data"][2] . "\r\n" );
					sleep( 1 );
					send( $server, "JOIN " . $server_output["data"][2] . "\r\n" );
				}
			}
			if ( $server_output["data"][1] == "PRIVMSG" ) {
				//Explode data 3 variable
				$server_output["data3-exp"] = array_map( 'trim', explode( ' ', $server_output["data"][3] ) );
				//BNC-mod specific code
				if ( strtolower( $server_output["data"][2] ) == "#stormbnc" || $server_output["data"][2] == $server["botnick"] ) {
					if ( strtolower( $server_output["data3-exp"][0] ) == "!request" ) {
						if ( $bncfreeze ) {
							send( $server, "PRIVMSG #stormbnc :Read the topic and the notice you were sent on entry. Request failed.\r\n" );
							@consoleout( "BNC Module", "Account request FAILED (Freeze in effect): " . $bncname . "  " . $bncemail . " by user " . $server_output["data"][0] );
						} else {
							if ( $server_output["data3-exp"][1] != null && $server_output["data3-exp"][2] != null ) {
								//sanity checking
								//enforced strtolower() and alphanumeric characters only on all inputs.
								$bncname     = strtolower( preg_replace( "/[^A-Za-z0-9]/", '', $server_output["data3-exp"][1] ) );
								$bncemail    = strtolower( preg_replace( "/[^A-Za-z0-9@.+]/", '', $server_output["data3-exp"][2] ) );
								$requestgood = true;
								foreach ( $bncusers as $key => $value ) {
									//$requestgood=true;
									if ( $bncname == strtolower( $key ) ) {
										$requestgood = false;
									}
									//if (!isset($bncusers[$key]["addr"])){
									if ( $bncemail == strtolower( $bncusers[$key]["addr"] ) ) {
										$requestgood = false;
									}
									//}
								}
								if ( $requestgood == true ) {
									send( $server, "PRIVMSG #stormbnc :Request for " . $bncname . " submitted. BNC Administrators will approve your request manually.\r\n" );
									//set the variable in our array
									$bncusers[$bncname]           = array();
									$bncusers[$bncname]["addr"]   = $bncemail;
									$bncusers[$bncname]["status"] = "wait";
									//send requested account email
									bncMail( $bncname, $bncemail, "request" );
									//save and log
									file_put_contents( "./bncusers.json", json_encode( $bncusers ) );
									@consoleout( "BNC Module", "Account requested: " . $bncname . "  " . $bncemail . " by user " . $server_output["data"][0] );
								} else {
									send( $server, "PRIVMSG #stormbnc :Whoops, looks like either the username or email you provided is in use already.\r\n" );
								}
							} else {
								send( $server, "PRIVMSG #stormbnc Please use '!request <BNC Username> <Email>' to request your BNC.\r\n" );
							}
						}
					} elseif ( strtolower( $server_output["data3-exp"][0] ) == "!help" ) {
						send( $server, "NOTICE " . $server_output["from"][0] . " :\x1fStormBNC Registration Help\r\n" );
						send( $server, "NOTICE " . $server_output["from"][0] . " :!rules - Lists BNC rules. Please note you are also subject to StormBit's network policy, and #stormbit's channel rules. See http://stormbit.net/ for more information.\r\n" );
						if ( !$bncfreeze ) {
							send( $server, "NOTICE " . $server_output["from"][0] . " :!request <username> <email> - Requests a BNC account. Email must be valid and \x1fnot\x1f from a temporary mail provider\r\n" );
						}else {
							send( $server, "NOTICE " . $server_output["from"][0] . " :\x02\x037BNC ACCOUNT FREEZE CURRENTLY IN EFFECT. ACCOUNT REQUESTS WILL BE IGNORED.\r\n" );
						}
						if ( in_array( $server_output["from"][0], $bncadmins ) === true ) {
							send( $server, "NOTICE " . $server_output["from"][0] . " :\x1fAdministrative account approval/listing commands\r\n" );
							if ( !$bncfreeze ) {
								send( $server, "NOTICE " . $server_output["from"][0] . " :!approve <username> - Approves a request listed in !list, sends an email to the user, and creates the account with a temporary password.\r\n" );
								send( $server, "NOTICE " . $server_output["from"][0] . " :!deny <username> - Denies a request listed in !list.\r\n" );
							}
							send( $server, "NOTICE " . $server_output["from"][0] . " :!list - Lists unapproved users.\r\n" );
							send( $server, "NOTICE " . $server_output["from"][0] . " :!listapproved - Lists approved users. Please run the clearoutputfile maintenance command below after running this.\r\n" );
							send( $server, "NOTICE " . $server_output["from"][0] . " :!listdenied - Lists denied users. Please run the clearoutputfile maintenance command below after running this.\r\n" );
							send( $server, "NOTICE " . $server_output["from"][0] . " :\x1fAdministrative user control commands\r\n" );
							send( $server, "NOTICE " . $server_output["from"][0] . " :!block <username> - Blocks an account using ZNC mod_blockuser.\r\n" );
							send( $server, "NOTICE " . $server_output["from"][0] . " :!unblock <username> - Blocks an account using ZNC mod_blockuser.\r\n" );
							send( $server, "NOTICE " . $server_output["from"][0] . " :\x1fAdministrative maintenance commands\r\n" );
							send( $server, "NOTICE " . $server_output["from"][0] . " :!maint bot del <account> - Deletes the bot's record of this account being requested, created, or denied. \x02Do not use on deleted BNC accounts\r\n" );
							send( $server, "NOTICE " . $server_output["from"][0] . " :!maint bot clearoutputfile - Clears the bot output file located at list. \x02Please do this after using !listdenied and !listapproved\r\n" );
							send( $server, "NOTICE " . $server_output["from"][0] . " :!maint bnc regenerate <account> - Completely deletes and regenerates the stock BNC account, wiping any user-made changes. \x02Do not use to reset passwords.\r\n" );
							send( $server, "NOTICE " . $server_output["from"][0] . " :!maint bnc resetpass <account> - Resets and resends the password email for an account. \x02Do not use to lock accounts.\r\n" );
						}
						@consoleout( "BNC Module", "Help requested by " . $server_output["data"][0] );
					} elseif ( strtolower( $server_output["data3-exp"][0] ) == "!rules" ) {
						send( $server, "NOTICE " . $server_output["from"][0] . " :\x02StormBNC Rules\r\n" );
						send( $server, "NOTICE " . $server_output["from"][0] . " :!request <username> <email> - Requests a BNC account. The email needs to be valid. Please don't request more than one BNC account or harass the admins for approval.\r\n" );
						send( $server, "NOTICE " . $server_output["from"][0] . " :Duplicate accounts are forbidden.\r\n" );
						send( $server, "NOTICE " . $server_output["from"][0] . " :Temporary mail providers are forbidden.\r\n" );
						send( $server, "NOTICE " . $server_output["from"][0] . " :Maintain a connection to this channel.\r\n" );
						send( $server, "NOTICE " . $server_output["from"][0] . " :Do not use our service to flood, spam, or avoid bans on other networks and channels.\r\n" );
						send( $server, "NOTICE " . $server_output["from"][0] . " :Do not ask to be unbanned from networks or channels.\r\n" );
						send( $server, "NOTICE " . $server_output["from"][0] . " :Do not ask for your account to be approved.\r\n" );
						send( $server, "NOTICE " . $server_output["from"][0] . " :\x02Failure to comply with any of these rules can result in account deletion.\r\n" );
						send( $server, "NOTICE " . $server_output["from"][0] . " :\x02You are also expected to comply with StormBit network policy, and #stormbit's channel rules regardless of where you are connected or what channels you may be in. See http://stormbit.net/ for more information.\r\n" );
						@consoleout( "BNC Module", "Help requested by " . $server_output["data"][0] );
					}

					//BNC administrator commands
					if ( in_array( $server_output["from"][0], $bncadmins ) === true ) {

						if ( strtolower( $server_output["data3-exp"][0] ) == "!list" ) {
							//bnc admin asking for unapproved users list, better send it.
							send( $server, "NOTICE " . $server_output["from"][0] . " :StormBNC Unapproved Request List. Confirm or Deny requests with !confirm <user> or !deny <user>\r\n" );
							foreach ( $bncusers as $key => $value ) {
								if ( $bncusers[$key]["status"] == "wait" ) {
									send( $server, "NOTICE " . $server_output["from"][0] . " :User " . $key . " - Email " . $bncusers[$key]["addr"] . "\r\n" );
								}
							}
							file_put_contents( "./bncusers.json", json_encode( $bncusers ) );
							@consoleout( "BNC Module", "Requests listed by admin " . $server_output["data"][0] );
						} elseif ( strtolower( $server_output["data3-exp"][0] ) == "!approve" ) {
							if ( $bncfreeze ) {
								send( $server, "PRIVMSG #stormbnc :BNC Account Creation Freeze currently in effect. Approval failed.\r\n" );
								@consoleout( "BNC Module", "Account approval FAILED (Freeze in effect): A:" . $bncname . "  E:" . $bncusers[$bncname]["addr"] . " by admin " . $server_output["data"][0] );
							} else {
								$bncname = preg_replace( "/[^A-Za-z0-9]/", '', $server_output["data3-exp"][1] );
								if ( array_key_exists( $bncname, $bncusers ) === true ) {
									if ( $bncusers[$bncname]["status"] == "wait" ) {
										//user entry exists, approve it
										send( $server, "PRIVMSG #stormbnc :BNC User " . $bncname . " has been approved. Sending email and creating account.\r\n" );
										//generate a password
										$bncusers[$bncname]["bncpass"] = substr( md5( microtime() . $bncname ), rand( -10, -6 ) );
										//ZNC-user prefix is ^. 
										$bncusers[$bncname]["status"]  = "active";
										//might use a template and clone users.
										send( $server, "PRIVMSG ^controlpanel :adduser " . $bncname . " " . $bncusers[$bncname]["bncpass"] . "\r\n" );
										send( $server, "PRIVMSG ^controlpanel :set maxnetworks " . $bncname . " 5\r\n" );
										send( $server, "PRIVMSG ^controlpanel :set quitmsg " . $bncname . " StormBNC - Free 5-Network BNC Service - http://stormbit.net/help/stormbnc\r\n" );
										send( $server, "PRIVMSG ^controlpanel :set Nick " . $bncname . " UnconfiguredStormBNCUser\r\n" );
										send( $server, "PRIVMSG ^controlpanel :set Ident " . $bncname . " UnconfiguredStormBNCUser\r\n" );
										send( $server, "PRIVMSG ^controlpanel :set RealName " . $bncname . " UnconfiguredStormBNCUser\r\n" );
										bncMail( $bncname, $bncusers[$bncname]["addr"], "approve", $bncusers[$bncname]["bncpass"] );
										@consoleout( "BNC Module", "Account APPROVED: " . $bncname . "  " . $bncusers[$bncname]["addr"] . " by admin " . $server_output["data"][0] );
									} else {
										send( $server, "PRIVMSG #stormbnc :BNC username was not found in waiting list. Are they active or denied?\r\n" );
									}
								} else {
									send( $server, "PRIVMSG #stormbnc :BNC username was not found in request list.\r\n" );
								}
								file_put_contents( "./bncusers.json", json_encode( $bncusers ) );
							}
						} elseif ( strtolower( $server_output["data3-exp"][0] ) == "!deny" ) {
							if ( $bncfreeze ) {
								send( $server, "PRIVMSG #stormbnc :BNC Account Creation Freeze currently in effect. Denial failed.\r\n" );
								@consoleout( "BNC Module", "Account denial FAILED (Freeze in effect): A:" . $bncname . "  E:" . $bncusers[$bncname]["addr"] . " by admin " . $server_output["data"][0] );
							} else {
								//bnc admin denying a request
								//filter user input. 
								$bncname = preg_replace( "/[^A-Za-z0-9]/", '', $server_output["data3-exp"][1] );
								if ( array_key_exists( $bncname, $bncusers ) === true ) {
									//user entry exists, delete it.
									$bncusers[$bncname]["status"] = "deny";
									send( $server, "PRIVMSG #stormbnc :BNC request for " . $bncname . " was denied.\r\n" );
									@consoleout( "BNC Module", "Account denied: " . $bncname . "  " . key( $bncusers[$bncname] ) . " by admin " . $server_output["data"][0] );
								} else {
									send( $server, "PRIVMSG #stormbnc :BNC username was not found in request list.\r\n" );
								}
								file_put_contents( "./bncusers.json", json_encode( $bncusers ) );
								@consoleout( "BNC Module", "Accounts denied list requested: " . $bncname . "  " . key( $bncusers[$bncname] ) . " by admin " . $server_output["data"][0] );
							}
						} elseif ( strtolower( $server_output["data3-exp"][0] ) == "!listapproved" ) {
							//bnc admin looking for a list of approved users. this list is HUGE
							send( $server, "NOTICE " . $server_output["from"][0] . " :Generating list of active users...\r\n" );
							
							$fp = fopen( "bot-output.txt", "w" );
							fwrite( $fp, "StormBNC Active Users List\n" );
							fclose( $fp );
							$fp = fopen( "bot-output.txt", "a" );
							foreach ( $bncusers as $key => $value ) {
								if ( $bncusers[$key]["status"] == "active" ) {
									fwrite( $fp, "User " . $key . " - Email " . $bncusers[$key]["addr"] . "\n" );
									//send( $server, "NOTICE " . $server_output["from"][0] . " :User " . $key . " - Email " . $bncusers[$key]["addr"] . "\r\n" );
								}
							}
							fclose( $fp );
							send( $server, "NOTICE " . $server_output["from"][0] . " :Generation complete. See list.\r\n" );
							@consoleout( "BNC Module", "Accounts approved list requested by admin " . $server_output["data"][0] );
						} elseif ( strtolower( $server_output["data3-exp"][0] ) == "!listdenied" ) {
							//bnc admin looking for list of denied users
							send( $server, "NOTICE " . $server_output["from"][0] . " :Generating list of denied users...\r\n" );
							$fp = fopen( "bot-output.txt", "w" );
							fwrite( $fp, "StormBNC Denied Users List\n" );
							fclose( $fp );
							$fp = fopen( "bot-output.txt", "a" );
							foreach ( $bncusers as $key => $value ) {
								if ( $bncusers[$key]["status"] == "deny" ) {
									fwrite( $fp, "User " . $key . " - Email " . $bncusers[$key]["addr"] ."\n");
								}
							}
							fclose( $fp );
							send( $server, "NOTICE " . $server_output["from"][0] . " :Generation complete. See list.\r\n" );
							@consoleout( "BNC Module", "Accounts denied list requested by admin " . $server_output["data"][0] );
						} elseif ( strtolower( $server_output["data3-exp"][0] ) == "!block" ) {
							//bnc admin attempting to block an account
							send( $server, "PRIVMSG #stormbnc :BNC account ".$server_output["data3-exp"][1]." blocked.\r\n" );
							send( $server, "PRIVMSG ^blockuser :block ".$server_output["data3-exp"][1]."\r\n" );
							@consoleout( "BNC Module", "BNC account ".$server_output["data3-exp"][1]." BLOCKED by admin " . $server_output["data"][0] );
						} elseif ( strtolower( $server_output["data3-exp"][0] ) == "!unblock" ) {
							//bnc admin attempting to block an account
							send( $server, "PRIVMSG #stormbnc :BNC account ".$server_output["data3-exp"][1]." unblocked.\r\n" );
							send( $server, "PRIVMSG ^blockuser :unblock ".$server_output["data3-exp"][1]."\r\n" );
							@consoleout( "BNC Module", "BNC account ".$server_output["data3-exp"][1]." UNBLOCKED by admin " . $server_output["data"][0] );
						} elseif ( strtolower( $server_output["data3-exp"][0] ) == "!maint" ) {
							//bnc admin using maintenance system
								if (strtolower($server_output["data3-exp"][1]) == "bot" ){
									if (strtolower($server_output["data3-exp"][2]) == "del" ){
										$cleaned = preg_replace( "/[^A-Za-z0-9]/", '', $server_output["data3-exp"][3] );
										if ($bncusers[$cleaned] !== '' && $cleaned !== ""){
											//make sure we're not just sending in a NULL or erasing the whole goddamn array
											unset($bncusers[$cleaned]);
											send($server, "PRIVMSG #stormbnc :Done.\r\n");
										}else{ 
											send($server, "PRIVMSG #stormbnc :Failed.\r\n");
										}
									}
									if (strtolower($server_output["data3-exp"][2]) == "clearoutputfile" ){
										$fp = fopen( "bot-output.txt", "w" );
										fwrite( $fp, "Nothing to see here.\n" );
										fclose( $fp );
										send($server, "PRIVMSG #stormbnc :Done.\r\n");
									}
								}elseif (strtolower($server_output["data3-exp"][1]) == "bnc" ){
									if (strtolower($server_output["data3-exp"][2]) == "regenerate" ){
										$cleaned = preg_replace( "/[^A-Za-z0-9]/", '', $server_output["data3-exp"][3] );
										if ($bncusers[$cleaned] !== '' && $cleaned !== ""){
											send( $server, "PRIVMSG #stormbnc :BNC User " . $cleaned . " ACCOUNT REGENERATED. Sending email.\r\n" );
											$temppass = substr( md5( microtime() . $cleaned ), rand( -10, -6 ) );
											$bncusers[$cleaned]["status"]  = "active";
											send( $server, "PRIVMSG ^controlpanel :deluser " . $cleaned . "\r\n" );
											send( $server, "PRIVMSG ^controlpanel :adduser " . $cleaned . " " . $temppass . "\r\n" );
											send( $server, "PRIVMSG ^controlpanel :set maxnetworks " . $cleaned . " 5\r\n" );
											send( $server, "PRIVMSG ^controlpanel :set quitmsg " . $cleaned . " StormBNC - Free 5-Network BNC Service - http://stormbit.net/help/stormbnc\r\n" );
											send( $server, "PRIVMSG ^controlpanel :set Nick " . $cleaned . " UnconfiguredStormBNCUser\r\n" );
											send( $server, "PRIVMSG ^controlpanel :set Ident " . $cleaned . " UnconfiguredStormBNCUser\r\n" );
											send( $server, "PRIVMSG ^controlpanel :set RealName " . $cleaned . " UnconfiguredStormBNCUser\r\n" );
											bncMail( $cleaned, $bncusers[$cleaned]["addr"], "approve", $bncusers[$cleaned]["bncpass"] );
											@consoleout( "BNC Module", "Account deleted and regenerated: " . $cleaned . "  " . $bncusers[$cleaned]["addr"] . " by admin " . $server_output["data"][0] );
										}else{ 
											send($server, "PRIVMSG #stormbnc :Failed.\r\n");
										}
									}elseif (strtolower($server_output["data3-exp"][2]) == "resetpass" ){
										$cleaned = preg_replace( "/[^A-Za-z0-9]/", '', $server_output["data3-exp"][3] );
										if ($bncusers[$cleaned] !== '' && $cleaned !== ""){
											//user entry exists, approve it
											send( $server, "PRIVMSG #stormbnc :BNC User " . $cleaned . " password reset. Sending email.\r\n" );
											//generate a password
											$temppass = substr( md5( microtime() . $cleaned ), rand( -10, -6 ) );
											send( $server, "PRIVMSG ^controlpanel :set password " . $cleaned . " " . $temppass . "\r\n" );
											bncMail( $cleaned, $bncusers[$cleaned]["addr"], "approve", $bncusers[$cleaned]["bncpass"] );
											@consoleout( "BNC Module", "Account password reset: " . $cleaned . "  " . $bncusers[$cleaned]["addr"] . " by admin " . $server_output["data"][0] );
										}else{ 
											send($server, "PRIVMSG #stormbnc :Failed.\r\n");
										}
									}
									
									//save bncusers
								}
							file_put_contents( "./bncusers.json", json_encode( $bncusers ) );
							@consoleout( "BNC Module", "BNC maintenance command executed by admin ".$server_output["data"][0]." : ".$server_output["data"][3]);
						}
					} //end bnc admin commands
				} //end #stormbnc specific commands
			} //end PRIVMSG parsing
			$server_output = array();
		}
	}
	usleep( 1 );
	$first = false;
}
?>
