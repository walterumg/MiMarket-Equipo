<?php
/** Núcleo común de MiMarket: BD, seguridad, vistas, correo, PDF y utilidades. */
class DB {
 private static ?PDO $pdo=null;
 static function get():PDO {
  if(!self::$pdo){
   $c=require __DIR__.'/../config/config.php';$d=$c['db'];
   $opt=[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_PERSISTENT=>false];
   if(!empty($d['ssl_ca']) && is_file($d['ssl_ca']))$opt[PDO::MYSQL_ATTR_SSL_CA]=$d['ssl_ca'];
   self::$pdo=new PDO("mysql:host={$d['host']};port={$d['port']};dbname={$d['name']};charset=utf8mb4",$d['user'],$d['pass'],$opt);
  } return self::$pdo;
 }
}
function view($viewName,$data=[]){extract($data);$config=require __DIR__.'/../config/config.php';ob_start();require __DIR__."/Views/$viewName.php";$content=ob_get_clean();require __DIR__.'/Views/layouts/main.php';}
function redirect($url){header('Location: '.$url,true,303);exit;}
function e($v){return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function auth(){return $_SESSION['user']??null;} function staff(){return auth()&&in_array(auth()['role']??'',['admin','operador'],true);} function admin(){return auth()&&auth()['role']==='admin';}
function requireAuth(){if(!auth()){flash('Inicia sesión para continuar.');redirect('/?r=login');}} function requireStaff(){if(!staff()){http_response_code(403);die('Acceso no autorizado');}} function requireAdmin(){if(!admin()){http_response_code(403);die('Acceso no autorizado');}}
function csrf(){if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(32));return $_SESSION['csrf'];}
function checkCsrf(){if(!isset($_POST['csrf'])||!hash_equals($_SESSION['csrf']??'',(string)$_POST['csrf'])){http_response_code(419);die('Solicitud inválida o expirada.');}}
function flash($m){$_SESSION['flash']=$m;}
function pageNum(){return max(1,(int)($_GET['page']??1));}
function paginateArray($rows,$per=10){$total=count($rows);$pages=max(1,(int)ceil($total/$per));$page=min(pageNum(),$pages);return ['rows'=>array_slice($rows,($page-1)*$per,$per),'page'=>$page,'pages'=>$pages,'total'=>$total];}
function pageUrl($page){$q=$_GET;$q['page']=$page;return '/?'.http_build_query($q);}
function passwordStrong($p){return strlen($p)>=8 && preg_match('/[A-Za-z]/',$p) && preg_match('/\d/',$p);}
function clientIp(){return $_SERVER['REMOTE_ADDR']??'unknown';}

/** Cliente SMTP pequeño, sin dependencias externas. Usa SMTP_HOST/PORT/USER/PASS. */
function sendMailSMTP($to,$subject,$html,$attachment=null,$attachmentName='factura.pdf'){
 $c=require __DIR__.'/../config/config.php';$m=$c['mail'];
 if(!$m['host']||!$m['user']||!$m['pass'])return false;
 $host=$m['host'];$port=(int)$m['port'];$secure=strtolower($m['secure']);$target=($secure==='ssl'?'ssl://':'').$host;
 $fp=@stream_socket_client($target.':'.$port,$errno,$errstr,12);

if(!$fp){
    error_log("MiMarket SMTP: No se pudo conectar a {$target}:{$port}. Error {$errno}: {$errstr}");
    return false;
}

stream_set_timeout($fp,12);
 $read=function()use($fp){$out='';while(($l=fgets($fp,515))!==false){$out.=$l;if(strlen($l)<4||$l[3]!=='-')break;}return $out;};
 $cmd=function($s,$codes)use($fp,$read){fwrite($fp,$s."\r\n");$r=$read();return in_array((int)substr($r,0,3),(array)$codes,true);};
 $read();$cmd('EHLO mimarket',[250]);
 if($secure==='tls'){if(!$cmd('STARTTLS',[220])){fclose($fp);return false;}if(!stream_socket_enable_crypto($fp,true,STREAM_CRYPTO_METHOD_TLS_CLIENT)){fclose($fp);return false;}$cmd('EHLO mimarket',[250]);}
 if(!$cmd('AUTH LOGIN',[334])||!$cmd(base64_encode($m['user']),[334])||!$cmd(base64_encode($m['pass']),[235])){fclose($fp);return false;}
 if(!$cmd('MAIL FROM:<'.$m['from'].'>',[250])||!$cmd('RCPT TO:<'.$to.'>',[250,251])||!$cmd('DATA',[354])){fclose($fp);return false;}
 $boundary='b'.bin2hex(random_bytes(8));$headers="From: {$m['from_name']} <{$m['from']}>\r\nTo: <$to>\r\nSubject: =?UTF-8?B?".base64_encode($subject)."?=\r\nMIME-Version: 1.0\r\nContent-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
 $body="--$boundary\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n".chunk_split(base64_encode($html));
 if($attachment!==null)$body.="\r\n--$boundary\r\nContent-Type: application/pdf; name=\"$attachmentName\"\r\nContent-Disposition: attachment; filename=\"$attachmentName\"\r\nContent-Transfer-Encoding: base64\r\n\r\n".chunk_split(base64_encode($attachment));
 $body.="\r\n--$boundary--\r\n";fwrite($fp,$headers."\r\n".$body."\r\n.\r\n");$ok=(int)substr($read(),0,3)===250;$cmd('QUIT',[221]);fclose($fp);return $ok;
}
function pdfEscape($s){$s=iconv('UTF-8','Windows-1252//TRANSLIT',(string)$s)?:$s;return str_replace(['\\','(',')'],['\\\\','\\(','\\)'],$s);}
/** Generador PDF tabular de MiMarket: encabezado, resumen, tabla y pie. */
function pdfTableDocument($title,$subtitle,$headers,$rows,$summary=[]){
 $W=595;$H=842;$margin=36;$page=1;$streams=[];$content='';$y=0;
 $esc=fn($s)=>pdfEscape((string)$s);
 $text=function($x,$yy,$txt,$size=9,$bold=false)use(&$content,$esc){$font=$bold?'F2':'F1';$content.="BT /$font $size Tf $x $yy Td (".$esc($txt).") Tj ET\n";};
 $line=function($x1,$y1,$x2,$y2)use(&$content){$content.="$x1 $y1 m $x2 $y2 l S\n";};
 $rect=function($x,$yy,$w,$h,$gray=.96)use(&$content){$content.="$gray g $x $yy $w $h re f 0 g\n";};
 $newPage=function()use(&$content,&$y,&$page,$title,$subtitle,$text,$rect,$W,$H,$margin){if($content!==''){$GLOBALS['__mm_pdf_streams'][]=$content;$content='';$page++;}$rect(0,$H-115,$W,115,.95);$text($margin,$H-55,'MiMarket',24,true);$text($margin,$H-78,$title,15,true);$text($margin,$H-96,$subtitle,8,false);$text($W-135,$H-55,'Documento generado',8,true);$text($W-135,$H-70,date('d/m/Y H:i'),8,false);$y=$H-135;};
 $GLOBALS['__mm_pdf_streams']=[];$newPage();
 if($summary){$x=$margin;foreach($summary as $k=>$v){$rect($x,$y-36,120,36,.92);$text($x+8,$y-14,$k,7,true);$text($x+8,$y-29,$v,10,true);$x+=128;if($x+120>$W-$margin){$y-=45;$x=$margin;}}$y-=55;}
 $cols=count($headers);$usable=$W-2*$margin;$cw=$usable/max(1,$cols);$drawHeader=function()use(&$y,$headers,$cols,$cw,$margin,$rect,$text,$line,$W){$rect($margin,$y-22,$W-2*$margin,22,.86);for($i=0;$i<$cols;$i++)$text($margin+$i*$cw+4,$y-15,substr((string)$headers[$i],0,24),8,true);$line($margin,$y-22,$W-$margin,$y-22);$y-=22;};$drawHeader();
 $rowOnPage=0;foreach($rows as $r){if($y<65||$rowOnPage>=10){$text($margin,30,'MiMarket · Reporte generado automáticamente',7);$text($W-80,30,'Página '.$page,7,true);$GLOBALS['__mm_pdf_streams'][]=$content;$content='';$page++;$rect(0,$H-115,$W,115,.95);$text($margin,$H-55,'MiMarket',24,true);$text($margin,$H-78,$title,15,true);$text($margin,$H-96,'Continuación · '.date('d/m/Y H:i'),8);$y=$H-135;$drawHeader();$rowOnPage=0;}$rect($margin,$y-20,$W-2*$margin,20,($rowOnPage%2===0?1:.97));for($i=0;$i<$cols;$i++)$text($margin+$i*$cw+4,$y-14,substr((string)($r[$i]??''),0,28),7.5);$line($margin,$y-20,$W-$margin,$y-20);$y-=20;$rowOnPage++;}
 $text($margin,30,'MiMarket · Reporte generado automáticamente',7);$text($W-80,30,'Página '.$page,7,true);$GLOBALS['__mm_pdf_streams'][]=$content;$streams=$GLOBALS['__mm_pdf_streams'];unset($GLOBALS['__mm_pdf_streams']);
 $objs=[];$pageIds=[];$obj=3;foreach($streams as $st){$pageIds[]=$obj;$obj+=2;}$font1=$obj++;$font2=$obj++;$objs[1]='<< /Type /Catalog /Pages 2 0 R >>';$kids=implode(' ',array_map(fn($id)=>$id.' 0 R',$pageIds));$objs[2]='<< /Type /Pages /Kids ['.$kids.'] /Count '.count($pageIds).' >>';$i=0;foreach($streams as $st){$pid=$pageIds[$i];$cid=$pid+1;$objs[$pid]='<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 '.$font1.' 0 R /F2 '.$font2.' 0 R >> >> /Contents '.$cid.' 0 R >>';$objs[$cid]='<< /Length '.strlen($st).' >>'."\nstream\n$st\nendstream";$i++;}$objs[$font1]='<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';$objs[$font2]='<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';ksort($objs);$pdf="%PDF-1.4\n";$offs=[0];$max=max(array_keys($objs));for($n=1;$n<=$max;$n++){$offs[$n]=strlen($pdf);$pdf.="$n 0 obj\n".($objs[$n]??'<<>>')."\nendobj\n";}$xref=strlen($pdf);$pdf.="xref\n0 ".($max+1)."\n0000000000 65535 f \n";for($n=1;$n<=$max;$n++)$pdf.=sprintf('%010d 00000 n ',$offs[$n])."\n";$pdf.="trailer << /Size ".($max+1)." /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";return $pdf;
}
function invoicePdf($o){$rows=[];foreach($o['items'] as $i)$rows[]=[(string)$i['quantity'],($i['sku']??''),$i['name'],'Q'.number_format($i['unit_price'],2),'Q'.number_format($i['subtotal'],2)];$summary=['Pedido'=>'#'.$o['id'],'Cliente'=>$o['fullname'],'Pago'=>$o['payment_status'],'TOTAL'=>'Q'.number_format($o['total'],2)];return pdfTableDocument('FACTURA DE COMPRA','Fecha: '.$o['created_at'].' · '.$o['email'].' · '.$o['address'],['Cant.','SKU','Producto','Precio','Subtotal'],$rows,$summary);}

function sendSecurityHeaders(){header('X-Content-Type-Options: nosniff');header('X-Frame-Options: DENY');header('Referrer-Policy: strict-origin-when-cross-origin');header("Permissions-Policy: camera=(), microphone=(), geolocation=()");header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; script-src 'self' 'unsafe-inline'; font-src 'self' https://cdn.jsdelivr.net; connect-src 'self'; frame-ancestors 'none'");header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');header('Pragma: no-cache');}
