<?php
/**
* s2Member's PayPal IPN handler (inner processing routines).
*
* Copyright: © 2009-2011
* {@link http://www.websharks-inc.com/ WebSharks, Inc.}
* (coded in the USA)
*
* Released under the terms of the GNU General Public License.
* You should have received a copy of the GNU General Public License,
* along with this software. In the main directory, see: /licensing/
* If not, see: {@link http://www.gnu.org/licenses/}.
*
* @package s2Member\PayPal
* @since 3.5
*/
if (realpath (__FILE__) === realpath ($_SERVER["SCRIPT_FILENAME"]))
	exit ("Do not access this file directly.");

if (!class_exists ("c_ws_plugin__s2member_paypal_notify_in"))
	{
		/**
		* s2Member's PayPal IPN handler (inner processing routines).
		*
		* @package s2Member\PayPal
		* @since 3.5
		*/
		class c_ws_plugin__s2member_paypal_notify_in
			{
				/**
				* Handles PayPal IPN processing.
				*
				* These same routines also handle s2Member Pro/PayPal Pro operations;
				* giving you the ability *(as needed)* to Hook into these routines using
				* WordPress Hooks/Filters; as seen in the source code below.
				*
				* Please do NOT modify the source code directly.
				* Instead, use WordPress Hooks/Filters.
				*
				* For example, if you'd like to add your own custom conditionals, use:
				* ``add_filter ("ws_plugin__s2member_during_paypal_notify_conditionals", "your_function");``
				*
				* @package s2Member\PayPal
				* @since 3.5
				*
				* @attaches-to ``add_action("init");``
				*
				* @return null Or exits script execution after handling IPN procesing.
				*/
				public static function paypal_notify ()
					{
						global /* For Multisite support. */ $current_site, $current_blog;

						do_action ("ws_plugin__s2member_before_paypal_notify", get_defined_vars ());

						if (!empty ($_GET["s2member_paypal_notify"]) && ($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["paypal_business"] || !empty ($_REQUEST["s2member_paypal_proxy"])))
							{
								@ignore_user_abort (true); // Important. Continue processing even if/when the connection is broken by the sending party.

								include_once ABSPATH . "wp-admin/includes/admin.php"; // Get administrative functions. Needed for `wp_delete_user()`.

								$email_configs_were_on = c_ws_plugin__s2member_email_configs::email_config_status (); // Filters on?
								c_ws_plugin__s2member_email_configs::email_config_release (); // Release s2Member Filters.

								if (is_array ($paypal = c_ws_plugin__s2member_paypal_utilities::paypal_postvars ()) && ($_paypal = $paypal) && ($_paypal_s = serialize ($_paypal)))
									{
										$paypal["s2member_log"][] = "IPN received on: " . date ("D M j, Y g:i:s a T");
										$paypal["s2member_log"][] = "s2Member POST vars verified " . ((!empty ($paypal["proxy_verified"])) ? "with a Proxy Key" : "through a POST back to PayPal.");

										$payment_status_issues = "/^(failed|denied|expired|refunded|partially_refunded|reversed|reversal|canceled_reversal|voided)$/i";

										$paypal["subscr_gateway"] = (!empty ($_REQUEST["s2member_paypal_proxy"])) ? esc_html (trim (stripslashes ($_REQUEST["s2member_paypal_proxy"]))) : "paypal";

										$coupon = (!empty($_REQUEST["s2member_paypal_proxy_coupon"]) && is_array($_REQUEST["s2member_paypal_proxy_coupon"])) ? stripslashes_deep($_REQUEST["s2member_paypal_proxy_coupon"]) : array();
										$coupon = (isset($coupon["full_coupon_code"], $coupon["coupon_code"], $coupon["affiliate_id"]) && is_string($coupon["full_coupon_code"]) && is_string($coupon["coupon_code"]) && is_string($coupon["affiliate_id"])) ? $coupon : array("full_coupon_code" => "", "coupon_code" => "", "affiliate_id" => "");

										if (empty ($paypal["custom"]) && !empty ($paypal["recurring_payment_id"])) // Lookup on Recurring Profiles?
											$paypal["custom"] = c_ws_plugin__s2member_utils_users::get_user_custom_with ($paypal["recurring_payment_id"]);

										if (!empty ($paypal["custom"]) && preg_match ("/^" . preg_quote (preg_replace ("/\:([0-9]+)$/", "", $_SERVER["HTTP_HOST"]), "/") . "/i", $paypal["custom"]))
											{
												$paypal["s2member_log"][] = "s2Member originating domain ( `\$_SERVER[\"HTTP_HOST\"]` ) validated.";

												foreach(array_keys(get_defined_vars())as$__v)$__refs[$__v]=&$$__v;
												if (!apply_filters ("ws_plugin__s2member_during_paypal_notify_conditionals", false, get_defined_vars ()))
													{
														unset /* Unset defined __refs, __v. */ ($__refs, $__v);

														if (($_paypal_cp = c_ws_plugin__s2member_paypal_notify_in_virtual_terminal::cp (get_defined_vars ())))
															$paypal = $_paypal_cp;

														else if (($_paypal_cp = c_ws_plugin__s2member_paypal_notify_in_express_checkout::cp (get_defined_vars ())))
															$paypal = $_paypal_cp;

														else if (($_paypal_cp = c_ws_plugin__s2member_paypal_notify_in_cart::cp (get_defined_vars ())))
															$paypal = $_paypal_cp;

														else if (($_paypal_cp = c_ws_plugin__s2member_paypal_notify_in_send_money::cp (get_defined_vars ())))
															$paypal = $_paypal_cp;

														else if (($_paypal_cp = c_ws_plugin__s2member_paypal_notify_in_web_accept_sp::cp (get_defined_vars ())))
															$paypal = $_paypal_cp;

														else if (($_paypal_cp = c_ws_plugin__s2member_paypal_notify_in_wa_ccaps_wo_level::cp (get_defined_vars ())))
															$paypal = $_paypal_cp;

														else if (($_paypal_cp = c_ws_plugin__s2member_paypal_notify_in_subscr_or_wa_w_level::cp (get_defined_vars ())))
															$paypal = $_paypal_cp;

														else if (($_paypal_cp = c_ws_plugin__s2member_paypal_notify_in_rec_profile_creation_w_level::cp (get_defined_vars ())))
															$paypal = $_paypal_cp;

														else if (($_paypal_cp = c_ws_plugin__s2member_paypal_notify_in_subscr_modify_w_level::cp (get_defined_vars ())))
															$paypal = $_paypal_cp;

														else if (($_paypal_cp = c_ws_plugin__s2member_paypal_notify_in_subscr_or_rp_payment_w_level::cp (get_defined_vars ())))
															$paypal = $_paypal_cp;

														else if (($_paypal_cp = c_ws_plugin__s2member_paypal_notify_in_subscr_or_rp_payment_failed_w_level::cp (get_defined_vars ())))
															$paypal = $_paypal_cp;

														else if (($_paypal_cp = c_ws_plugin__s2member_paypal_notify_in_subscr_or_rp_cancellation_w_level::cp (get_defined_vars ())))
															$paypal = $_paypal_cp;

														else if (($_paypal_cp = c_ws_plugin__s2member_paypal_notify_in_subscr_or_rp_eots_w_level::cp (get_defined_vars ())))
															$paypal = $_paypal_cp;

														else if (($_paypal_cp = c_ws_plugin__s2member_paypal_notify_in_sp_refund_reversal::cp (get_defined_vars ())))
															$paypal = $_paypal_cp;

														else // Ignoring this IPN request. The txn_type/status does NOT require any action.
															$paypal["s2member_log"][] = "Ignoring this IPN request. The `txn_type/status` does NOT require any action on the part of s2Member.";
													}
												else // Else a custom conditional has been applied by Filters.
													unset /* Unset defined __refs, __v. */ ($__refs, $__v);
											}

										else if (!empty ($paypal["txn_type"]) && preg_match ("/^recurring_payment_profile_cancel$/i", $paypal["txn_type"]))
											{
												$paypal["s2member_log"][] = "Transaction type ( `recurring_payment_profile_cancel` ), but there is no match to an existing account; so verification of `\$_SERVER[\"HTTP_HOST\"]` was not possible.";
												$paypal["s2member_log"][] = "It's likely this account was just upgraded/downgraded by s2Member Pro; so the Subscr. ID has probably been updated on-site; nothing to worry about here.";
											}

										else if (!empty ($paypal["txn_type"]) && preg_match ("/^recurring_/i", $paypal["txn_type"])) // Otherwise, is this a ^recurring_ txn_type?
											$paypal["s2member_log"][] = "Transaction type ( `^recurring_?` ), but there is no match to an existing account; so verification of `\$_SERVER[\"HTTP_HOST\"]` was not possible.";

										else // Else, use the default ``$_SERVER["HTTP_HOST"]`` error.
											$paypal["s2member_log"][] = "Unable to verify `\$_SERVER[\"HTTP_HOST\"]`. Please check the `custom` value in your Button Code. It MUST start with your domain name.";
									}
								else // Extensive log reporting here. This is an area where many site owners find trouble. Depending on server configuration; remote HTTPS connections may fail.
									{
										$paypal["s2member_log"][] = "Unable to verify \$_POST vars. This is most likely related to an invalid configuration of s2Member, or a problem with server compatibility.";
										$paypal["s2member_log"][] = "Please see this KB article: `http://www.s2member.com/kb/server-scanner/`. We suggest that you run the s2Member Server Scanner.";
										$paypal["s2member_log"][] = var_export ($_REQUEST, true); // Recording _POST + _GET vars for analysis and debugging.
									}
								if ($email_configs_were_on) // Back on?
									c_ws_plugin__s2member_email_configs::email_config ();
								/*
								Add IPN proxy (when available) to the ``$paypal`` array.
								*/
								if (!empty ($_REQUEST["s2member_paypal_proxy"]))
									$paypal["s2member_paypal_proxy"] = $_REQUEST["s2member_paypal_proxy"];
								/*
								Add IPN proxy use vars (when available) to the ``$paypal`` array.
								*/
								if (!empty ($_REQUEST["s2member_paypal_proxy_use"]))
									$paypal["s2member_paypal_proxy_use"] = $_REQUEST["s2member_paypal_proxy_use"];
								/*
								Add IPN proxy coupon vars (when available) to the ``$paypal`` array.
								*/
								if (!empty ($_REQUEST["s2member_paypal_proxy_coupon"]))
									$paypal["s2member_paypal_proxy_coupon"] = $_REQUEST["s2member_paypal_proxy_coupon"];
								/*
								Also add IPN proxy self-verification (when available) to the ``$paypal`` array.
								*/
								if (!empty ($_REQUEST["s2member_paypal_proxy_verification"]))
									$paypal["s2member_paypal_proxy_verification"] = $_REQUEST["s2member_paypal_proxy_verification"];
								/*
								If debugging/logging is enabled; we need to append ``$paypal`` to the log file.
									Logging now supports Multisite Networking as well.
								*/
								$logt = c_ws_plugin__s2member_utilities::time_details ();
								$logv = c_ws_plugin__s2member_utilities::ver_details ();
								$logm = c_ws_plugin__s2member_utilities::mem_details ();
								$log4 = $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] . "\nUser-Agent: " . $_SERVER["HTTP_USER_AGENT"];
								$log4 = (is_multisite () && !is_main_site ()) ? ($_log4 = $current_blog->domain . $current_blog->path) . "\n" . $log4 : $log4;
								$log2 = (is_multisite () && !is_main_site ()) ? "gateway-core-ipn-4-" . trim (preg_replace ("/[^a-z0-9]/i", "-", $_log4), "-") . ".log" : "gateway-core-ipn.log";

								if ($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["gateway_debug_logs"])
									if (is_dir ($logs_dir = $GLOBALS["WS_PLUGIN__"]["s2member"]["c"]["logs_dir"]))
										if (is_writable ($logs_dir) && c_ws_plugin__s2member_utils_logs::archive_oversize_log_files ())
											file_put_contents ($logs_dir . "/" . $log2,
											                   "LOG ENTRY: ".$logt . "\n" . $logv . "\n" . $logm . "\n" . $log4 . "\n" .
											                                            c_ws_plugin__s2member_utils_logs::conceal_private_info(var_export ($paypal, true)) . "\n\n",
											                   FILE_APPEND);

								foreach(array_keys(get_defined_vars())as$__v)$__refs[$__v]=&$$__v;
								do_action ("ws_plugin__s2member_during_paypal_notify", get_defined_vars ());
								unset /* Unset defined __refs, __v. */ ($__refs, $__v);

								status_header (200); // Send a 200 OK status header.
								header ("Content-Type: text/plain; charset=UTF-8"); // Content-Type text/plain with UTF-8.
								while (@ob_end_clean ()); // Clean any existing output buffers.

								exit (((!empty ($paypal["s2member_paypal_proxy_return_url"])) ? $paypal["s2member_paypal_proxy_return_url"] : ""));
							}
						foreach(array_keys(get_defined_vars())as$__v)$__refs[$__v]=&$$__v;
						do_action ("ws_plugin__s2member_after_paypal_notify", get_defined_vars ());
						unset /* Unset defined __refs, __v. */ ($__refs, $__v);
					}
			}
	}
?>