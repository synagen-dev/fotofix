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
	if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
		// Gemini API returned text response - this means it understood the instructions
		$aiResponse = $response['candidates'][0]['content']['parts'][0]['text'];
		
		// Check if the response indicates successful understanding (eg. "this is the enhanced image")
		if (strpos(strtolower($aiResponse), 'error') === false && 
			(strpos(strtolower($aiResponse), 'enhance') !== false || 
			 strpos(strtolower($aiResponse), 'improve') !== false ||
			 strpos(strtolower($aiResponse), 'modify') !== false)) {
			echo "Response OK.. processing<BR>"); 
		
			// Extract returned image
			if (isset($response['candidates'][0]['content']['parts'][1]['inlineData'])) {
				if (isset($response['candidates'][0]['content']['parts'][1]['inlineData']['mimeType'])) $mimeType=$response['candidates'][0]['content']['parts'][1]['inlineData']['mimeType'];
				if (isset($response['candidates'][0]['content']['parts'][1]['inlineData']['mimeType']['data'])) $returnedImage=$response['candidates'][0]['content']['parts'][1]['inlineData']['mimeType']['data'];
				if($mimeType==='image/png')$outfile='response.png';
				$fout=fopen($outfile,'w');
				fwrite($fout,$returnedImage);
				fclose($fout);	
				echo 'Image output ok. <BR><img src="'.$outfile.'"><BR>';			
			}
		} else {
			 echo 'Gemini API response indicates issues';
		}
	}else echo "Unable to find text part";
}
?>
</body>
</html>
