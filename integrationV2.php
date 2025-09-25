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

// Check if the operation was successful
if ($response === false) {
    echo "Error: Could not read file contents.</body></html>";
} else {
	if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
		// Gemini API returned text response - this means it understood the instructions
		$aiResponse = $response['candidates'][0]['content']['parts'][0]['text'];
		
		// Check if the response indicates successful understanding (eg. "this is the enhanced image")
		if (strpos(strtolower($aiResponse), 'error') === false && 
			(strpos(strtolower($aiResponse), 'enhance') !== false || 
			 strpos(strtolower($aiResponse), 'improve') !== false ||
			 strpos(strtolower($aiResponse), 'modify') !== false)) {
			echo "Reasponse OK.. processing<BR>"); 
		
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
			// AI understood the instructions, use enhanced processing
			//return $this->enhancedFallbackEnhancement($imagePath, $outputPath, $instructions);
		} else {
			 echo'Gemini API response indicates issues');
		}
	}else echo "Unable to find text part";
}
  
?>
</body>
</html>
