<?php  
include "httpclient.php";

$httpClient = new elHttpClient();
$httpClient->setUserAgent("ff3");
$httpClient->enableRedirects(true);


$code = "072140801014";
$code = "3253581052723";
//$code = "075678307065";

// check OUR DB
// check MISSING DB (1 week old)
// find ONLINE

if (isset($_GET['code'])) $code = trim($_GET['code']);

$htmlDataObject = $httpClient->get("http://www.checkupc.com/search.php?keyword=".$code);
$htmlData = $htmlDataObject->httpBody;


$title = $company = $img = '';

if (strpos($htmlData, "Your search didn't match any products.") === false){
  $htmlData = cutOut($htmlData,"</form>");
  $htmlData = cutOut($htmlData,"<table","</table>");
  
  $img1 = cutOut($htmlData,"","</td>");
  if (strpos($img1,"<img") !== false){
    $img = cutOut($img1,'src="','"');
  }
  
  $title = cutOut($htmlData,"<h1");
  $title = trim(strip_tags(cutOut($title,">","</h1>")));
  
  $htmlData = cutOut($htmlData,"</h1>");
  $company = '';
  if (strpos($htmlData,"Other products by") !== false){
    $company = trim(strip_tags(cutOut($htmlData,"Other products by","</a>")));
  }
}else{
  // try to find code somewhere else
  $httpClient->setReferer("http://m.eandata.com/lookup/".$code."/");
  $htmlDataObject = $httpClient->post("http://m.eandata.com/lookup.php","extra=x&code=".$code."&mode=prod&ajax=1");
  
  $htmlData = $htmlDataObject->httpBody;
  
  if (strpos($htmlData,">Prod.</td>") !== false){
    $title = trim(strip_tags(cutOut($htmlData,">Prod.</td>","</td>")));

    $htmlData = cutOut($htmlData,"<h3>Company</h3>");
    $company = trim(strip_tags(cutOut($htmlData,"<td>Name</td>","</td>")));


    $htmlData = cutOut($htmlData,'prod_img',"</div>");
    if (strpos($htmlData,"<img") !== false){
      $img = cutOut($htmlData,'<img');
      $img = cutOut($img,"src='","'");

      // no image
      if (strpos($htmlData,"no-product-image") !== false) $img = '';
    }

  }else{
    // last chance
    $httpClient->setReferer("http://www.upcdatabase.com");
    $htmlDataObject = $httpClient->get("http://www.upcdatabase.com/item/".$code);
    $htmlData = $htmlDataObject->httpBody;
    
    if (strpos($htmlData,"Item Not Found") === false){
      $htmlData = cutOut($htmlData,"<td>Description</td>");
      $title = trim(strip_tags(cutOut($htmlData,"</td>","</td>")));
    }
  }
  
}

// write output
if ($title == '') echo $code." NOT FOUND!";
else{
  echo $title."<br />".$company."<br />";
  echo '<img src="'.$img.'">';
}







 function cutOut($string, $start, $end = '', $leaveIn = false, $case = true){
    $lenStart = strlen($start);
    $lenEnd = 0;
    if ($leaveIn){
      $lenStart = 0;
      $lenEnd = strlen($end);
    }
    $result = $string;
    // get start and end of string
    if ($case){
      if ($start) $result = mb_substr($string,strpos($string,$start)+$lenStart);
      if ($end) $result = mb_substr($result,0,strpos($result,$end)+$lenEnd);
    }else{
      if ($start) $result = mb_substr($string,stripos($string,$start)+$lenStart);
      if ($end) $result = mb_substr($result,0,stripos($result,$end)+$lenEnd);
    }
    return $result;
  }


  function moveOut($string, $start, $end = '', $leaveIn = false, $case = true){
    $count = 0;
    if ($start){
      if ($case) $count = strpos($string, $start);
      else $count = stripos($string, $start);
    }

    $cut = cutOut($string, $start, $end, $leaveIn, $case);

    if ($leaveIn) $count += strlen($cut);
    else $count += strlen($start.$end.$cut);

    return array("orig" => mb_substr($string,$count),
                 "sub" => $cut);
  }
//http://www.checkupc.com/search.php?keyword=2342352345234