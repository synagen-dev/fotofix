<html>
<head>
</head>
<body>
<?php
/**
 * cut down version of google_AI_integration.php.
 Takes json response stored in log file and processes it and outputs to user
 */
  
$filename = 'response.json'; 
$response = file_get_contents($filename);
if ($response === false) {
    echo "Error: Could not read file contents.";
} else {
	$decodedResponse = json_decode($response, true);
	if (isset($decodedResponse['candidates'][0]['content']['parts'][0]['text'])) {
		// Gemini API returned text response - this means it understood the instructions
		$aiResponse = $decodedResponse['candidates'][0]['content']['parts'][0]['text'];
		
		// Check if the response indicates successful understanding (eg. "this is the enhanced image")
		if (strpos(strtolower($aiResponse), 'error') === false && 
			(strpos(strtolower($aiResponse), 'enhance') !== false || 
			 strpos(strtolower($aiResponse), 'improve') !== false ||
			 strpos(strtolower($aiResponse), 'modify') !== false)
			 ) 
		{
			echo "Response OK.. processing<BR>"; 
		
			// Extract returned image
			if (isset($decodedResponse['candidates'][0]['content']['parts'][1]['inlineData'])) {
				if (isset($decodedResponse['candidates'][0]['content']['parts'][1]['inlineData']['mimeType'])) {
					$mimeType=$decodedResponse['candidates'][0]['content']['parts'][1]['inlineData']['mimeType'];
					echo "mimeType=$mimeType <BR>";
				}
				if (isset($decodedResponse['candidates'][0]['content']['parts'][1]['inlineData']['data'])) {
					$returnedImage=$decodedResponse['candidates'][0]['content']['parts'][1]['inlineData']['data'];
					echo "Got data<BR>";
					if($mimeType==='image/png')$outfile='response.png';
					$fout=fopen($outfile,'w');
					fwrite($fout,$returnedImage);
					fclose($fout);	
					echo 'Image output ok. <BR><img src="'.$outfile.'"><BR>';			
				}else echo "data not found<BR>";
			}
		} else {
			 echo 'Gemini API response indicates issues';
		}
	}else echo "Unable to find text part";
}
?>
</body>
</html>
