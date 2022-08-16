<?php	
function toEnglishDecimal($number)
{
	$formattedNumber = number_format($number, 2, '.', '');
	return $formattedNumber;
}

function toEnglishNumber($number)
{
	$formattedNumber = number_format($number);
	return $formattedNumber;
}

function br2nl($string)
{
    return preg_replace('/\<br(\s*)?\/?\>/i', "\n", $string);
} 
	
function generateRandomPassword()
{
	$passwordLength = 12;
	return generateRandomString($passwordLength);
}

function checkIfStringContainsEmail($txtStr)
{ 
	$hasEmail = false;
    if (preg_match('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/si', $txtStr))
	{
		$hasEmail = true;
	}
	return $hasEmail;
}

function sanitizeContactNoString($contactNoStr) 
{
	$sanStr = '';
	if(isset($contactNoStr) && trim($contactNoStr) != '')
	{
		$sanStr = trim($contactNoStr);
		$sanStr = filter_var($sanStr, FILTER_SANITIZE_NUMBER_INT);
		$sanStr = str_replace('+', '', $sanStr);
		$sanStr = str_replace('-', '', $sanStr);
	}
    return $sanStr;
}

function sanitizeEmailString($emailStr) 
{
	$sanStr = '';
	if(isset($emailStr) && trim($emailStr) != '')
	{
		$sanStr = trim($emailStr);
		$sanStr = strtolower($sanStr);
		$sanStr = filter_var($sanStr, FILTER_SANITIZE_EMAIL);
	}
    return $sanStr;
}

function sanitizeContactNoStringForMapping($contactNoStr) 
{
	$sanStr = '';
	if(isset($contactNoStr) && trim($contactNoStr) != '')
	{
		$sanStr = trim($contactNoStr);
		$sanStr = filter_var($sanStr, FILTER_SANITIZE_NUMBER_INT);
		$sanStr = str_replace('+', '', $sanStr);
		$sanStr = str_replace('-', '', $sanStr);

		$sanStr = substr($sanStr, -10);
	}
    return $sanStr;
}

function generateRandomString($length = 10) 
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-@';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function pdfHeaderCallback($pdf)
{	
	$pdf->SetY(5);
    $pdf->SetFont('times', '', 12);
    
	$org_name = Config::get('app_config.org_name');
	$org_addr_line_1 = Config::get('app_config.addr_line_1');
	$org_addr_line_2 = Config::get('app_config.addr_line_2');
	$addr_city = Config::get('app_config.addr_city');
	$addr_phone = Config::get('app_config.addr_phone');
	$comp_mob = Config::get('app_config.comp_mob');
	$comp_website = Config::get('app_config.comp_website');
	$comp_email = Config::get('app_config.comp_email');
	$comp_gst_tin_number = Config::get('app_config.comp_gst_tin_number');
	$comp_cst_tin_number = Config::get('app_config.comp_cst_tin_number');
	$comp_ecc_number = Config::get('app_config.comp_ecc_number');
	$comp_range = Config::get('app_config.comp_reg_range');
	$comp_division = Config::get('app_config.comp_reg_division');
	$comp_commissirate = Config::get('app_config.comp_reg_commissirate');
	
	$html1='
		<table width="100%">
			<tr>
				<td width="40%">
					<b>'.strtoupper($org_name)."</b><br/>".$org_addr_line_1.",";
					
	if($org_addr_line_2 != "")
		$html1 .= "<br/>".$org_addr_line_2.",";
	
	if($addr_city != '')
		$html1.="<br/>".$addr_city;
		
	if($addr_phone != '')
		$html1.="<br/>Ph: ".$addr_phone;
	
	$html1 .= '</td>
				<td width="60%" align="right">'.
				'GST ID: '.$comp_gst_tin_number.'<br/>'.
				'ECC: '.$comp_ecc_number.'<br/>'.
				'Range: '.$comp_range.' Division: '.$comp_division.'<br/>'.
				'Commissirate: '.$comp_commissirate.''.
			'</td>
		</tr>
	</table>';
		
	$html2 = '<div><hr/>
		<table width="100%" style="padding-bottom: 3px !important;">
			<tr>
				<td width="50%">Email: '.$comp_email.'
				</td>
				<td width="50%" align="right">
				Web: '.$comp_website.'&nbsp;&nbsp;
				</td>
			</tr>
		</table>
		<hr/>
	</div>';
	
	 //if ($pdf->getAliasNumPage() == 1) 
	 {
        $image_file = url(Config::get('app_config.assetBasePath').Config::get('app_config.company_logo_letterhead'));
		
        $pdf->Image($image_file, 8, 5, 22, '', 'PNG', '', 'T', true, 300, 'L', false, false, 0, false, false, false);
		
		$pdf->SetMargins(PDF_MARGIN_LEFT+18, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);		
		$pdf->writeHTML($html1, true, false, true, true, 'L');
		
		$pdf->SetMargins(PDF_MARGIN_LEFT-5, PDF_MARGIN_TOP+8, PDF_MARGIN_RIGHT-5);		
		$pdf->writeHTML($html2, true, false, true, true, 'L');
     }
}

function pdfFooterCallback($pdf)
{
	$org_regd_addr_line_1 = Config::get('app_config.org_regd_addr_line_1');
	$org_regd_addr_line_2 = Config::get('app_config.org_regd_addr_line_2');
    // Position at 15 mm from bottom
    $pdf->SetY(-15);
    // Set font
    $pdf->SetFont('helvetica', 'I', 8);
	
	$html1 = <<<EOF
	
<div style="background-color:#880000;color:white;text-align:center;">Regd.office: $org_regd_addr_line_1 $org_regd_addr_line_2 </div>
EOF;

	// output the HTML content
	//if (count($this->pages) === 1) 
	//{	
	//	$this->writeHTML($html1, true, false, true, false, '');
	//}
    // Page number
	$pdf->Cell(0, 3, 'Powered By iTechnoSol', 0, false, 'L', 0, '', 0, false, 'T', 'M');
	$pdf->Cell(0, 3, 'Page '.$pdf->getAliasNumPage().' of '.$pdf->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
}
	
/**
* Valid Date in DMY format
* @param   string
* @return  boolean
*/
function makeDate($val)
{	
	if(strlen($val) > 10)
	{
		$val = substr($val,0,10);
	}
	$val = str_replace('/', '-', $val);
	return $val;
}
	
/**
* Valid Date in DMY format
* @param   string
* @return  boolean
*/
function isValidDMYDate($val)
{
	$result = FALSE;
	
	if(strlen($val) > 10)
	{
		$val = substr($val,0,10);
	}
	
	$dateArray = array();			
	$dateArray = explode('-', $val);
	
	if(count($dateArray) != 3)
		$dateArray = explode('/', $val);
	
	if(count($dateArray) == 3){
		if(checkdate(intval($dateArray[1]),intval($dateArray[0]),intval($dateArray[2])))
			$result = TRUE;
	}
	return $result;
}
	
/**
* Valid Date in DMY format
* @param   string
* @return  boolean
*/
function isValidYMDDate($val)
{
	$result = FALSE;
	
	if(strlen($val) > 10)
	{
		$val = substr($val,0,10);
	}
	
	$dateArray = array();			
	$dateArray = explode('-', $val);
	
	if(count($dateArray) != 3)
		$dateArray = explode('/', $val);
	
	if(count($dateArray) == 3){
		if(checkdate(intval($dateArray[1]),intval($dateArray[2]),intval($dateArray[0])))
			$result = TRUE;
	}
	return $result;
}

/**
* Valid Date in DMY format
* @param   string
* @return  boolean
*/
function isValidEmail($val)
{
	$result = FALSE;
	if(filter_var($val, FILTER_VALIDATE_EMAIL))
		$result = TRUE;
	return $result;
}

function dispToDbDate($dtStr)
{
	$dt = "";
	
	if($dtStr != NULL && $dtStr != "" && $dtStr != "00-00-0000" && $dtStr != "01-01-1970")
	{
   	 	$dt = date(Config::get('app_config.date_db_format'), strtotime($dtStr));
	}
	 	
 	return $dt;
}

function dbToDispDate($dtStr)
{
	$dt = "";
	
	if($dtStr != NULL && $dtStr != "" && $dtStr != "0000-00-00" && $dtStr != "1970-01-01")
	{
   	 	$dt = date(Config::get('app_config.date_disp_format'), strtotime($dtStr));
	}
	 	
 	return $dt;
}

function dbToLongDispDateTime($dtStr)
{
	$dt = "";
	
	if($dtStr != NULL && $dtStr != "" && $dtStr != "0000-00-00 00:00:00" && $dtStr != "1970-01-01 00:00:00")
	{		
		$tz = new DateTimeZone("+330");
		$date = new DateTime($dtStr);
		$date->setTimezone($tz);
		$dt = $date->format('l F j, Y g:i A');
	}
	 	
 	return $dt;
}

function dbToDispDateTimeWithTZOffset_temp($ts, $consOffsetInMinutes)
{
	$utcTs = intval($ts/1000);

	//set timezone
	date_default_timezone_set('UTC');

	$date = new DateTime();
	$date->setTimestamp($utcTs);
	$unixTimestamp = time() + ($consOffsetInMinutes * 60); // 30 * 60

	$date = new DateTime();
	$date->setTimestamp($unixTimestamp);
	$dtTimeStr = $date->format('U = Y-m-d H:i:s');

	\Log::info('unixTimestamp : '.$unixTimestamp.' : dtTimeStr : '.$dtTimeStr);

	return $dtTimeStr;
}

function dbToDispDateTimeWithTZOffset($ts, $consOffsetInMinutes)
{
	\Log::info('ts : '.$ts);
	$utcTs = intval($ts/1000);
	\Log::info('utcTs : '.$utcTs);
	$locDt = \Carbon\Carbon::createFromTimeStampUTC($utcTs);

	$b4TzTimeStr = $locDt->format('d-m-Y h:m a'); 
									
	if(!is_nan($consOffsetInMinutes) && $consOffsetInMinutes != 0)
	{
		$offsetIsNegative = 0;
		if($consOffsetInMinutes < 0)
		{
			$offsetInMinutes = $consOffsetInMinutes*-1;
		}
		else
		{
			$offsetIsNegative = 1;
			$offsetInMinutes = $consOffsetInMinutes;
		}
		
		$offsetMinutes = $offsetInMinutes % 60;						
		$offsetHours = ($offsetInMinutes - $offsetMinutes)/60;

		\Log::info('offsetInMinutes : '.$offsetInMinutes.' : offsetHours : '.$offsetHours.' : offsetMinutes : '.$offsetMinutes);
		
		if($offsetIsNegative == 1)
		{
			\Log::info('offsetIsNegative == 1');
			/*
			if($offsetHours > 0)
			{
				\Log::info('subHours : offsetHours : '.$offsetHours);
				$locDt = $locDt->subHours($offsetHours);
				$aftrHour = $locDt->format('d-m-Y h:m a');
				\Log::info('aftrHour : '.$aftrHour);
			}	
			if($offsetMinutes > 0)
			{
				\Log::info('subMinutes : offsetMinutes : '.$offsetMinutes);
				$locDt = $locDt->subMinutes($offsetMinutes);
				$aftrMin = $locDt->format('d-m-Y h:m a');
				\Log::info('aftrMin : '.$aftrMin);
			}	
			*/	
			$locDt = $locDt->subMinutes($offsetInMinutes);
		}
		else
		{
			\Log::info('offsetIsNegative == 0');
			/*
			if($offsetHours > 0)
			{	
				\Log::info('addHours : offsetHours : '.$offsetHours);
				$locDt = $locDt->addHours($offsetHours);
				$aftrHour = $locDt->format('d-m-Y h:m a');
				\Log::info('aftrHour : '.$aftrHour);
			}		
			if($offsetMinutes > 0)
			{
				\Log::info('addMinutes : offsetMinutes : '.$offsetMinutes);
				$locDt = $locDt->addMinutes($offsetMinutes);
				$aftrMin = $locDt->format('d-m-Y h:m a');
				\Log::info('aftrMin : '.$aftrMin);
			}	
			*/	
			$locDt = $locDt->addMinutes($offsetInMinutes);			
		}							
	}
					
	$dtTimeStr = "";
	if(isset($locDt) && $locDt != "")
	{
		$dtTimeStr = $locDt->format('d-m-Y h:m a'); 
	}
	\Log::info('b4TzTimeStr : '.$b4TzTimeStr.' : dtTimeStr : '.$dtTimeStr);
	 	
 	return $dtTimeStr;
}

function dbToDispDateTimeWithTZ($ts, $tzStr)
{
	$utcTs = intval(round($ts/1000));
	$locDt = \Carbon\Carbon::createFromTimeStampUTC($utcTs);
	$locDt->tz = $tzStr;
					
	$dtTimeStr = "";
	if(isset($locDt) && $locDt != "")
	{
		$dtTimeStr = $locDt->format('d-m-Y h:i a'); 
	}
	 	
 	return $dtTimeStr;
}

function dbDateTimeToDispDateTimeWithDefaultTZ($dtTimeStr)
{			
	$tzStr = "Asia/Calcutta";
	$dtTimeStr = dbDateTimeToDispDateTimeWithTZ($dtTimeStr, $tzStr);
 	return $dtTimeStr;
}

function dbDateTimeToDispDateTimeWithTZ($dtTimeStr, $tzStr)
{
	$locDt = \Carbon\Carbon::parse($dtTimeStr, 'UTC');
	$locDt->tz = $tzStr;
					
	$dtTimeStr = "";
	if(isset($locDt) && $locDt != "")
	{
		$dtTimeStr = $locDt->format('d-m-Y h:i a'); 
	}
	 	
 	return $dtTimeStr;
}

function getExtensionFromFilename($filename) 
{
	return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

function checkIfFileTypeImageFromFileName($filename) 
{
	$ext = getExtensionFromFilename($filename);
	return checkIfFileTypeImageFromExtension($ext);
}

function checkIfFileTypeImageFromExtension($ext) 
{
	if (strpos($ext, '.') !== false) {
	    $ext = str_ireplace([ '.' ], '', $ext);
	}
	
	$isTypeImage = FALSE;
	$imgTypeExtStr = Config::get('app_config.attachment_type_image_extensions');
	$imgTypeExtArr = explode(',', $imgTypeExtStr);
	if(in_array($ext, $imgTypeExtArr)) 
	{
		$isTypeImage = TRUE;
	}
	return $isTypeImage;
}

function checkIfFileTypeVideoFromFileName($filename) 
{
	$ext = getExtensionFromFilename($filename);
	return checkIfFileTypeVideoFromExtension($ext);
}

function checkIfFileTypeVideoFromExtension($ext) 
{
	if (strpos($ext, '.') !== false) {
	    $ext = str_ireplace([ '.' ], '', $ext);
	}
	
	$isTypeVideo = FALSE;
	$vidTypeExtStr = Config::get('app_config.attachment_type_video_extensions');
	$vidTypeExtArr = explode(',', $vidTypeExtStr);
	if(in_array($ext, $vidTypeExtArr)) 
	{
		$isTypeVideo = TRUE;
	}
	return $isTypeVideo;
}

function getExtensionStringFromFilename($filename) 
{
	$ext = getExtensionFromFilename($filename);
	if (strpos($ext, '.') !== false) {
	    $ext = str_ireplace([ '.' ], '', $ext);
	}
	return $ext;
}

function getMimeTypeFromFilename($filename) 
{
	$fileExt = getExtensionStringFromFilename($filename);
	$fileExt = strtolower($fileExt);
	$fileMimeType = ''; // mime_content_type($filename);

	if (!(strpos($fileExt, '.') !== false)) {
		$fileExt = '.' . $fileExt ;
	}

	switch($fileExt) 
	{
			case '.aac': $mime ='audio/aac'; break; // AAC audio
			case '.abw': $mime ='application/x-abiword'; break; // AbiWord document
			case '.avi': $mime ='video/x-msvideo'; break; // AVI: Audio Video Interleave
			case '.bmp': $mime ='image/bmp'; break; // Windows OS/2 Bitmap Graphics
			case '.csv': $mime ='text/csv'; break; // Comma-separated values (CSV)
			case '.doc': $mime ='application/msword'; break; // Microsoft Word
			case '.docx': $mime ='application/vnd.openxmlformats-officedocument.wordprocessingml.document'; break; // Microsoft Word (OpenXML)
			case '.eot': $mime ='application/vnd.ms-fontobject'; break; // MS Embedded OpenType fonts
			case '.gif': $mime ='image/gif'; break; // Graphics Interchange Format (GIF)
			case '.htm': $mime ='text/html'; break; // HyperText Markup Language (HTML)
			case '.html': $mime ='text/html'; break; // HyperText Markup Language (HTML)
			case '.ico': $mime ='image/x-icon'; break; // Icon format
			case '.ics': $mime ='text/calendar'; break; // iCalendar format
			case '.jar': $mime ='application/java-archive'; break; // Java Archive (JAR)
			case '.jpeg': $mime ='image/jpeg'; break; // JPEG images
			case '.jpg': $mime ='image/jpeg'; break; // JPEG images
			case '.js': $mime ='application/javascript'; break; // JavaScript (IANA Specification) (RFC 4329 Section 8.2)
			case '.json': $mime ='application/json'; break; // JSON format
			case '.mid': $mime ='audio/midi audio/x-midi'; break; // Musical Instrument Digital Interface (MIDI)
			case '.midi': $mime ='audio/midi audio/x-midi'; break; // Musical Instrument Digital Interface (MIDI)
			case '.mpeg': $mime ='video/mpeg'; break; // MPEG Video
			case '.mpkg': $mime ='application/vnd.apple.installer+xml'; break; // Apple Installer Package
			case '.odp': $mime ='application/vnd.oasis.opendocument.presentation'; break; // OpenDocument presentation document
			case '.ods': $mime ='application/vnd.oasis.opendocument.spreadsheet'; break; // OpenDocument spreadsheet document
			case '.odt': $mime ='application/vnd.oasis.opendocument.text'; break; // OpenDocument text document
			case '.oga': $mime ='audio/ogg'; break; // OGG audio
			case '.ogv': $mime ='video/ogg'; break; // OGG video
			case '.ogx': $mime ='application/ogg'; break; // OGG
			case '.otf': $mime ='font/otf'; break; // OpenType font
			case '.png': $mime ='image/png'; break; // Portable Network Graphics
			case '.pdf': $mime ='application/pdf'; break; // Adobe Portable Document Format (PDF)
			case '.ppt': $mime ='application/vnd.ms-powerpoint'; break; // Microsoft PowerPoint
			case '.pptx': $mime ='application/vnd.openxmlformats-officedocument.presentationml.presentation'; break; // Microsoft PowerPoint (OpenXML)
			case '.rar': $mime ='application/x-rar-compressed'; break; // RAR archive
			case '.rtf': $mime ='application/rtf'; break; // Rich Text Format (RTF)
			case '.sh': $mime ='application/x-sh'; break; // Bourne shell script
			case '.svg': $mime ='image/svg+xml'; break; // Scalable Vector Graphics (SVG)
			case '.swf': $mime ='application/x-shockwave-flash'; break; // Small web format (SWF) or Adobe Flash document
			case '.tar': $mime ='application/x-tar'; break; // Tape Archive (TAR)
			case '.tif': $mime ='image/tiff'; break; // Tagged Image File Format (TIFF)
			case '.tiff': $mime ='image/tiff'; break; // Tagged Image File Format (TIFF)
			case '.ts': $mime ='application/typescript'; break; // Typescript file
			case '.ttf': $mime ='font/ttf'; break; // TrueType Font
			case '.txt': $mime ='text/plain'; break; // Text, (generally ASCII or ISO 8859-n)
			case '.vsd': $mime ='application/vnd.visio'; break; // Microsoft Visio
			case '.wav': $mime ='audio/wav'; break; // Waveform Audio Format
			case '.weba': $mime ='audio/webm'; break; // WEBM audio
			case '.webm': $mime ='video/webm'; break; // WEBM video
			case '.webp': $mime ='image/webp'; break; // WEBP image
			case '.woff': $mime ='font/woff'; break; // Web Open Font Format (WOFF)
			case '.woff2': $mime ='font/woff2'; break; // Web Open Font Format (WOFF)
			case '.xhtml': $mime ='application/xhtml+xml'; break; // XHTML
			case '.xls': $mime ='application/vnd.ms-excel'; break; // Microsoft Excel
			case '.xlsx': $mime ='application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'; break; // Microsoft Excel (OpenXML)
			case '.xml': $mime ='application/xml'; break; // XML
			case '.xul': $mime ='application/vnd.mozilla.xul+xml'; break; // XUL
			case '.zip': $mime ='application/zip'; break; // ZIP archive
			case '.3gp': $mime ='video/3gpp'; break; // 3GPP audio/video container
			case '.3g2': $mime ='video/3gpp2'; break; // 3GPP2 audio/video container
			case '.mp3': $mime ='audio/mpeg'; break; // MP3 Audio
			case '.mp4': $mime ='video/mp4'; break; // MP3 Audio
			default: $mime = 'application/octet-stream' ; // general purpose MIME-type
	}

	return $mime;
}

function getDropBoxLoginURL()
{
    $clientId = env('DROPBOX_APP_KEY');
    $redirectUri = env('DROPBOX_REDIRECT_URI');
	$dropBoxLoginUrl = "https://www.dropbox.com/1/oauth2/authorize?response_type=code&client_id=".$clientId."&redirect_uri=".$redirectUri;

	return $dropBoxLoginUrl;
}

/* Use it for json_encode some corrupt UTF-8 chars
 * useful for = malformed utf-8 characters possibly incorrectly encoded by json_encode
 */
function utf8ize( $mixed ) 
{
    if (is_array($mixed)) {
        foreach ($mixed as $key => $value) {
            $mixed[$key] = utf8ize($value);
        }
    } elseif (is_string($mixed)) {
        return mb_convert_encoding($mixed, "UTF-8", "UTF-8");
    }
    return $mixed;
}

function sracEncryptStringData($strVal, $userSessionObj = NULL)
{
	$encStr = '';
	try
	{
		if(isset($strVal) && $strVal != "")
		{
			$crypter = NULL;

			$consKey = fetchUserSessionEncryptionKey($userSessionObj);
			if(isset($consKey))
			{
				$cipher = "aes-256-cbc";

				$iv_size = openssl_cipher_iv_length($cipher); 
				$iv = '1234567812345678';//openssl_random_pseudo_bytes($iv_size); 

				// \Illuminate\Support\Facades\Log::info('iv_size : '.$iv_size);

				// \Illuminate\Support\Facades\Log::info('iv : '.$iv);
 
				$encStr = openssl_encrypt($strVal, $cipher, $consKey, 0, $iv); 

				// \Illuminate\Support\Facades\Log::info('encStr : 1 : '.$encStr);

				// $encStr = urlencode($encStr);

				// \Illuminate\Support\Facades\Log::info('encStr : 2 : '.$encStr);

				// $encStr = ($encStr . ':' . $iv);

				// \Illuminate\Support\Facades\Log::info('encStr : 2 : '.$encStr);

				// $encStr = str_replace(' ', '', $encStr);

				// \Illuminate\Support\Facades\Log::info('encStr : 3 : '.$encStr);

				$encStr = base64_encode($encStr);

				// \Illuminate\Support\Facades\Log::info('encStr : 4 : '.$encStr);
			}
			else
			{
				$encStr = Crypt::encrypt($strVal);
			}
		}
	} 
    catch (RuntimeException $e) 
    {
    	//
   	}
	catch (Exception $e) 
	{
		//
	}
	finally
	{
		return $encStr;
	}
}

function sracEncryptNumberDataWithSanitization($numVal, $userSessionObj = NULL)
{
	$encStr = '';
	try
	{
		if(isset($numVal) && !is_nan($numVal))
		{
			$correctlyEncrypted = false;
			do
			{
				$encStr = sracEncryptStringData($numVal.'', $userSessionObj);

				if(strpos($encStr, '=') === false)
				{
					$correctlyEncrypted = true; 
				}
			}
			while(!$correctlyEncrypted);
		}
	} 
	catch (Exception $e) 
	{
		//
	}
	finally
	{
		return $encStr;
	}
}

function sracEncryptNumberData($numVal, $userSessionObj = NULL)
{
	$encStr = '';
	try
	{
		if(isset($numVal) && !is_nan($numVal))
		{
			$encStr = sracEncryptStringData($numVal.'', $userSessionObj);
		}
	} 
	catch (Exception $e) 
	{
		//
	}
	finally
	{
		return $encStr;
	}
}

function sracEncryptNumberArrayData($numValArr, $userSessionObj = NULL)
{
	$encStrArr = array();
	try
	{
		if(isset($numValArr) && is_array($numValArr) && count($numValArr) > 0)
		{
			foreach ($numValArr as $key => $numVal) 
			{
				$encStr = sracEncryptNumberData($numVal, $userSessionObj);

				$encStrArr[$key] = $encStr;
			}
		}
	} 
	catch (Exception $e) 
	{
		//
	}
	finally
	{
		return $encStrArr;
	}
}

function cleanStringForEncryptionKey($string) {
	$string = str_replace(' ', '', $string); // Replaces all spaces with hyphens.

	$str = preg_replace('/[^A-Za-z0-9]/', '', $string); // Removes special chars.

	if (strlen($str) > 20)
	{
		$str = substr($str, 0, 20);
	}

	return $str;
}

function fetchUserSessionEncryptionKey($userSessionObj)
{
	$consKey = NULL;
	if(isset($userSessionObj) && isset($userSessionObj->appuser->email) && $userSessionObj->appuser->email != "")
	{
		$sessionLoginToken = $userSessionObj->login_token;
		$appuserEmail = $userSessionObj->appuser->email;

		$prefix = 'h6q1mb';
		$postfix = 'jvzdlbwi';

		$consKey = $prefix . $sessionLoginToken . $postfix;

		$consKey = strtolower($consKey);

		$consKey = preg_replace("/[^a-z0-9]/", "", $consKey);

		// $consKeyLength = 32;

		// if(strlen($consKey) > $consKeyLength)
		// {
		// 	$consKey = substr($consKey, 0, $consKeyLength);
		// }
		// else if(strlen($consKey) < $consKeyLength)
		// {
		// 	$lengthNotAchieved = true;
		// 	do
		// 	{
		// 		$consKey .= 'h';
		// 		if(strlen($consKey) == $consKeyLength)
		// 		{
		// 			$lengthNotAchieved = false;
		// 		}
		// 	}
		// 	while($lengthNotAchieved);
		// }
	}
	return $consKey;
}

function sracDecryptStringData($encStr, $userSessionObj = NULL, $isRequired = true)
{
	$decStr = NULL;
	try
	{
		if(isset($encStr) && $encStr != "")
		{
			$crypter = NULL;

			$consKey = fetchUserSessionEncryptionKey($userSessionObj);
			if(isset($consKey))
			{
				$cipher = "aes-256-cbc";

				// \Illuminate\Support\Facades\Log::info('encStr : 1 : '.$encStr);

				$encStr = base64_decode($encStr); // base64_decode

				// \Illuminate\Support\Facades\Log::info('encStr : 2 : '.$encStr);

				// $encStr = str_replace(' ', '', $encStr);

				// \Illuminate\Support\Facades\Log::info('encStr : 3 : '.$encStr);

				// $parts = explode(':', $encStr);
				// if(isset($parts) && count($parts) == 2)
				{
					$actEncStr = $encStr;//$parts[0];
					// $iv = ($parts[1]);
					$iv = '1234567812345678';

					// \Illuminate\Support\Facades\Log::info('actEncStr : '.$actEncStr);

					// \Illuminate\Support\Facades\Log::info('iv : '.$iv);

					$decStr = openssl_decrypt($actEncStr, $cipher, $consKey, 0, $iv);

					// \Illuminate\Support\Facades\Log::info('decStr : '.$decStr);
				}
			}
			else
			{
				$decStr = Crypt::decrypt($encStr);
			}
		}
	} 
	catch (DecryptException $e) 
	{
		//
	}
	finally
	{
		if($isRequired && !isset($decStr))
		{
			$decStr = '';
		}
		return $decStr;
	}
}

function sracDecryptNumberData($numVal, $userSessionObj = NULL, $isRequired = true)
{
	$decNum = NULL;
	try
	{
		if(isset($numVal))
		{
			$decStr = sracDecryptStringData($numVal.'', $userSessionObj, false);
			if(isset($decStr) && $decStr != "" && !is_nan($decStr))
			{
				$decNum = $decStr * 1;
			}
		}
	} 
	catch (Exception $e) 
	{
		//
	}
	finally
	{
		if($isRequired && !isset($decNum))
		{
			$decNum = 0;
		}
		return $decNum;
	}
}

function sracDecryptNumberArrayData($numValArr, $userSessionObj = NULL, $isRequired = true)
{
	$decNumArr = NULL;
	try
	{
		if(isset($numValArr) && is_array($numValArr) && count($numValArr) > 0)
		{
			$decNumArr = array();
			foreach($numValArr as $numIndex => $numVal)
			{
				$decStr = sracDecryptStringData($numVal.'', $userSessionObj, false);
				if(isset($decStr) && $decStr != "" && !is_nan($decStr))
				{
					$decNum = $decStr * 1;
				}
				$decNumArr[$numIndex] = isset($decNum) ? $decNum : 0;
			}			
		}
	} 
	catch (Exception $e) 
	{
		//
	}
	finally
	{
		if($isRequired && !isset($decNumArr))
		{
			$decNumArr = array();
		}
		return $decNumArr;
	}
}

function jsonDecodeArrStringIfRequired($valArrStr, $isRequired = true)
{
	$consArrObj = NULL;
	try
	{
        if(isset($valArrStr))
        {
            if(!is_array($valArrStr) && trim($valArrStr) != "")
            {
        		$valArrStr = trim($valArrStr);
                $consArrObj = json_decode($valArrStr);
                if(!is_array($consArrObj))
                {
                    $consArrObj = array();
                }
            }
            else
            {
            	$consArrObj = $valArrStr;
            }
        }
	} 
	catch (Exception $e) 
	{
		//
	}
	finally
	{
		if($isRequired && (!isset($consArrObj) || !is_array($consArrObj)))
		{
			$consArrObj = array();
		}
		return $consArrObj;
	}
}

/**
* Encode data to Base64URL
* @param string $data
* @return boolean|string
*/
function base64url_encode($data)
{
	// First of all you should encode $data to Base64 string
	$b64 = base64_encode($data);

	// Make sure you get a valid result, otherwise, return FALSE, as the base64_encode() function do
	if ($b64 === false) {
		return false;
	}

	// Convert Base64 to Base64URL by replacing “+” with “-” and “/” with “_”
	$url = strtr($b64, '+/', '-_');

	// Remove padding character from the end of line and return the Base64URL result
	return rtrim($url, '=');
}

/**
* Decode data from Base64URL
* @param string $data
* @param boolean $strict
* @return boolean|string
*/
function base64url_decode($data, $strict = false)
{
	// Convert Base64URL to Base64 by replacing “-” with “+” and “_” with “/”
	$b64 = strtr($data, '-_', '+/');

	// Decode Base64 string and return the original data
	return base64_decode($b64, $strict);
}

function formatBytesStr($bytes, $precision = 2) { 
    $units = array('B', 'KB', 'MB', 'GB', 'TB'); 

    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 

    // Uncomment one of the following alternatives
    // $bytes /= pow(1024, $pow);
    // $bytes /= (1 << (10 * $pow)); 

    return round($bytes, $precision) . ' ' . $units[$pow]; 
}

function formatTimeStampToUTCDateTimeString($consTimestamp) { 
	$consTimestamp = round($consTimestamp/1000);
    $timeFormatStr = "h:i A";

	$utcDt = \Carbon\Carbon::createFromTimeStampUTC($consTimestamp);
	$utcDateStr = $utcDt->toFormattedDateString() . ' ' . $utcDt->format($timeFormatStr);
	return $utcDateStr;
}

function formatTimeStampToISTDateTimeString($consTimestamp) { 
	$consTimestamp = round($consTimestamp/1000);
	
    $timeFormatStr = "h:i A";
    $istOffsetHours = 5;
    $istOffsetMinutes = 30;

	$istDt = \Carbon\Carbon::createFromTimeStampUTC($consTimestamp);
	$istDt = $istDt->addHours($istOffsetHours);
	$istDt = $istDt->addMinutes($istOffsetMinutes);
	$istDateStr = $istDt->toFormattedDateString() . ' ' . $istDt->format($timeFormatStr);
	return $istDateStr;
}

function formatTimeStampToUTCAndISTDateTimeString($consTimestamp) { 
	$utcDateStr = formatTimeStampToUTCDateTimeString($consTimestamp);
	$istDateStr = formatTimeStampToISTDateTimeString($consTimestamp);

	$combinedDateStr = $utcDateStr . '(UTC)' . '/' . $istDateStr . '(IST)';
	return $combinedDateStr;
}

?>