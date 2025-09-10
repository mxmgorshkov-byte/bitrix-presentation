<?php
use mikehaertl\wkhtmlto\Image;

define('AI_PHOTOROOM_TOKEN', 'xxxxxxxxxxx');
define('LOG_FILE', $_SERVER["DOCUMENT_ROOT"] . '/errors.log');
define('IBLOCK_CATALOG', 1);

AddEventHandler('iblock', 'OnAfterIBlockElementUpdate', ['iblockHandlerClass', 'OnAfterIBlockElementUpdateHandlerIG']);


class ImageGenerator {
	private static function htmlToPngNew($html, $outputPath, $sizeX = 375, $sizeY = 500) {
		file_put_contents('/var/www/bmdev/data/www/padel.bmdev.ru/dev/test_img/wkhtmltoimageHTML_new.html', $html);
    	$options = [
			'height' => $sizeY,
			'width' => $sizeX,
		];
		$image = new Image($options);
		$image->setPage($html);

		if ($image->saveAs($outputPath)) {
			return true;
		} else {
			return "Ошибка: " . $image->getError();
		}
	}

	private static function imageToDataUrl(String $filename) : String {
		if(!file_exists($filename))
			return "File not found: " . $filename;

		$mime = mime_content_type($filename);
		if($mime === false) 
			return "Illegal MIME type for file: " . $filename;

		$raw_data = file_get_contents($filename);
		if($raw_data === false || empty($raw_data)) 
			return "File not readable or empty: " . $filename;

		return "data:{$mime};base64," . base64_encode($raw_data);
	}

	public static function generatePhotoRoomBackgroundWithPrompt(string $apiKey, string $imagePath, string $prompt, string $outputPath, $sizeX = 900, $sizeY = 1200) {
		if (!file_exists($imagePath)) 
			return "Ошибка: Файл изображения не найден по пути: " . $imagePath;

		$mimeType = mime_content_type($imagePath);
		if ($mimeType === false) {
			if (function_exists('finfo_open')) {
				$finfo = finfo_open(FILEINFO_MIME_TYPE);
				if ($finfo) {
					$mimeType = finfo_file($finfo, $imagePath);
					finfo_close($finfo);
				}
			}
			if ($mimeType === false) {
				$mimeType = 'application/octet-stream';
				return "Ошибка: не удалось определить MIME тип файла. Используется 'application/octet-stream'.";
			}
		}

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, "https://image-api.photoroom.com/v2/edit");

		curl_setopt($ch, CURLOPT_POST, 1);

		$cFile = curl_file_create($imagePath, $mimeType, basename($imagePath));

		$postFields = [
			'imageFile' => $cFile,
			'background.prompt' => $prompt,
			'referenceBox' => "originalImage",
			'outputSize' => $sizeX."x".$sizeY,
		];

		curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			"Accept: image/png",
			"x-api-key: " . $apiKey,
		]);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);

		$result = curl_exec($ch);

		if (curl_errno($ch)) {
			$error = curl_error($ch);
			curl_close($ch);
			return "Ошибка cURL: " . $error;
		}

		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

		if ($httpCode != 200) {
			try {
				$errorData = json_decode($result, true);
				$errorMessage = isset($errorData['error']) ? $errorData['error'] : 'Неизвестная ошибка';
				return "Ошибка PhotoRoom API: HTTP код " . $httpCode . ", сообщение: " . $errorMessage;
			} catch (Exception $e) {
				return "Ошибка PhotoRoom API: HTTP код " . $httpCode . ", тело ответа: " . $result;
			}
		}

		if (file_put_contents($outputPath, $result) === false) {
			return "Ошибка: Не удалось сохранить изображение в файл: " . $outputPath;
		}

		return $outputPath;
	}

	public static function bulidReadyHtml($variant, $image, $outputPath, $sizeX = 900, $sizeY = 1200, $txt_0 = "", $txt_1 = "", $txt_2 = "") : string {
		$logo = $_SERVER["DOCUMENT_ROOT"] . '/dev/test_img/logo_1.png';
		$bg_3 = $_SERVER["DOCUMENT_ROOT"] . '/dev/test_img/bg_3.png';

		$html = '
			<!DOCTYPE html>
			<html>
			<head>
				<meta charset="UTF-8">
				<meta name="viewport" content="width=device-width, initial-scale=1.0">
				<title>Overlaid Image</title>
				<style>
					@page { margin: 0; size: '.$sizeX.'px '.$sizeY.'px; }
					@font-face {
						font-family: "gt_eesti_pro_displayregular";
						src: url("https://'.$_SERVER['SERVER_NAME'].'/fonts/GTEestiProDisplay-Regular.ttf") format("ttf"),
							url("https://'.$_SERVER['SERVER_NAME'].'/fonts/GTEestiProDisplay-Regular.woff") format("woff"),
							url("https://'.$_SERVER['SERVER_NAME'].'/fonts/subset-GTEestiProDisplay-Regular.woff2") format("woff2");
						font-weight: normal;
						font-style: normal;
					}
					body { margin: 0; padding: 0; }
					.txt_wrap {
						width: auto;
						display: inline-block;
						font-family: "gt_eesti_pro_displayregular";
						margin: 0;
						position: absolute;
					}
					.img {
						width: 100%;
						height: 100%;
						object-fit: cover;
						position: absolute;
						left: 0;
						top: 0;
					}
					.container {
						position: relative;
						padding: 0;
						margin: 0;
						overflow: hidden;
						width: '.$sizeX.'px;
						height: '.$sizeY.'px;
					}
		';
		switch ($variant) {
			case 1:
				$html .= '
							.txt_wrap_0 {
								color: #fff;
								text-shadow: 1px 1px 2px #000;
								font-size: 30px;
								font-weight: 700;
								padding: 0px;
								left: 32px;
								top: 91px;
								font-family: "gt_eesti_pro_displayregular";
							}
							.txt_wrap_1 {
								background: #ffffff85;
								color: #fff;
								font-size: 45px;
								font-weight: 700;
								padding: 32px;
								padding-left: 42px;
								border-radius: 9px;
								left: -10px;
								top: 163px;
								font-family: "gt_eesti_pro_displayregular";
							}
							.logo {
								width: 900px;
								height: 485px;
								left: -30px;
								bottom: -10px;
								object-fit: cover;
								position: absolute;
							}
						</style>
					</head>
					<body>
						<div class="container">
							<img class="img" src="'.self::imageToDataUrl($image).'" alt="">
							<p class="txt_wrap txt_wrap_0">
								'.$txt_0.'
							</p>
							<p class="txt_wrap txt_wrap_1">
								'.$txt_1.'
							</p>
							<img class="logo" src="'.self::imageToDataUrl($logo).'" alt="">
						</div>
				';
				break;
			case 2:
				$html .= '
							.txt_wrap_0 {
								background: #ffffff85;
								color: #fff;
								font-size: 38px;
								font-weight: 700;
								padding: 32px;
								padding-left: 42px;
								border-radius: 9px;
								right: -22px;
								top: 44px;
								font-family: "gt_eesti_pro_displayregular";
							}
							.txt_wrap_1 {
								background: #AC8350;
								color: #fff;
								font-size: 38px;
								font-weight: 700;
								padding: 22px 60px;
								border-radius: 9px;
								left: -10px;
								top: 924px;
								font-family: "gt_eesti_pro_displayregular";
							}
							.txt_wrap_2 {
								background: #AC8350;
								color: #fff;
								font-size: 38px;
								font-weight: 700;
								padding: 22px 60px;
								border-radius: 9px;
								left: -10px;
								top: 1047px;
								font-family: "gt_eesti_pro_displayregular";
							}
						</style>
					</head>
					<body>
						<div class="container">
							<img class="img" src="'.self::imageToDataUrl($image).'" alt="">
							<p class="txt_wrap txt_wrap_0">
								Баланс: '.$txt_0.'
							</p>
							<p class="txt_wrap txt_wrap_1">
								Вес: '.$txt_1.'
							</p>
							<p class="txt_wrap txt_wrap_2">
								Форма: '.$txt_2.'
							</p>
						</div>
				';
				break;
			case 3:
				$html .= '
							.txt_wrap_0 {
								background: #ffffff85;
								color: #fff;
								font-size: 38px;
								font-weight: 700;
								padding: 32px;
								padding-left: 42px;
								border-radius: 9px;
								left: -10px;
								top: 44px;
								font-family: "gt_eesti_pro_displayregular";
							}
							.txt_wrap_1 {
								background: #AC8350;
								color: #fff;
								font-size: 38px;
								font-weight: 700;
								padding: 22px 60px;
								left: 0px;
								top: 981px;
								font-family: "gt_eesti_pro_displayregular";
								width: 100%;
								box-sizing: border-box;
							}
							.logo {
								width: 100%;
								heigth: 50%;
								object-fit: cover;
								position: absolute;
								left: 0;
								top: 0;
							}
						</style>
					</head>
					<body>
						<div class="container">
							<img class="img" src="'.self::imageToDataUrl($image).'" alt="">
							<img class="logo" src="'.self::imageToDataUrl($bg_3).'" alt="">
							<p class="txt_wrap txt_wrap_0">
								Подходит для игры в: '.$txt_0.'
							</p>
							<p class="txt_wrap txt_wrap_1">
								Уровень игры: '.$txt_1.'
							</p>
						</div>
				';
				break;
		}
		$html .= '
				</body>
			</html>
		';

		return self::htmlToPngNew($html, $outputPath, $sizeX, $sizeY);
	}
}

class iblockHandlerClass
{
	protected static $allowModify = true;

    public static function OnAfterIBlockElementUpdateHandlerIG(&$arParams)
	{
		if (!self::$allowModify) {
			return;
		}
		self::$allowModify = false;

		$propsIds = array_flip(array_column(PropertyTable::getList([
			'filter' => ['IBLOCK_ID' => $arParams['IBLOCK_ID']],
			'select' => ['ID', 'CODE'],
		])->fetchAll(), 'CODE', 'ID'));
		
		if ($arParams['IBLOCK_ID'] == IBLOCK_CATALOG && !empty($arParams['PROPERTY_VALUES'][$propsIds["IMG_GEN"]]) && reset($arParams['PROPERTY_VALUES'][$propsIds["IMG_GEN"]])['VALUE'] == "Y") {
			$apiKey = AI_PHOTOROOM_TOKEN;
			$imagePath = $_SERVER["DOCUMENT_ROOT"] . CFile::GetPath($arParams["DETAIL_PICTURE"]["old_file"]);
			$prompt = reset($arParams['PROPERTY_VALUES'][$propsIds["PROMPT_GEN"]])['VALUE'];$outputPath = $_SERVER["DOCUMENT_ROOT"] . '/dev/test_img/test_gen.jpg';
			$sizeX = 900;
			$sizeY = 1200;
			$preset = PropertyEnumerationTable::getList(['filter' => ['ID' => $arParams['PROPERTY_VALUES'][$propsIds["html_type"]][0]['VALUE']], 'limit' => 1])->fetch()['XML_ID'];

			$result = ImageGenerator::generatePhotoRoomBackgroundWithPrompt($apiKey, $imagePath, $prompt, $outputPath);

			if ($result === false) {
				error_log("Произошла ошибка при генерации фона. \n", 3, LOG_FILE);
			} elseif (is_string($result) && strpos($result, "Ошибка") !== false) {
				error_log($result . "\n", 3, LOG_FILE);
			} else {
				error_log("Фон успешно сгенерирован и сохранен в: " . $result . "\n", 3, LOG_FILE);
				$baseImagePath = $result;
			}

			if (file_exists($baseImagePath)) {
				$external_id = reset($arParams['PROPERTY_VALUES'][$propsIds["PROP_ONE_CODE"]])['VALUE'];
				$hldata = Bitrix\Highloadblock\HighloadBlockTable::getById(5)->fetch();
				$hlDataClass = $hldata["NAME"]."Table";

				$result = $hlDataClass::getList(array(
					"select" => array("UF_CODE"),
					"order" => array(),
					"filter" => array("UF_XML_ID"=>$external_id),
				));
				if($res = $result->fetch())
				{    
					$code_one = $res["UF_CODE"];
				}

				$external_id = reset($arParams['PROPERTY_VALUES'][$propsIds["PROP_ONE_CODE"]])['VALUE'];
				$result = $hlDataClass::getList(array(
					"select" => array("UF_CODE"),
					"order" => array(),
					"filter" => array("UF_XML_ID"=>$external_id),
				));
				if($res = $result->fetch())
				{    
					$code_two = $res["UF_CODE"];
				}
				
				switch ($preset) {
					case "1":	
						$type = reset($arParams['PROPERTY_VALUES'][$propsIds[$code_one]])['VALUE'];
						$name = reset($arParams['PROPERTY_VALUES'][$propsIds[$code_two]])['VALUE'];

						$success = ImageGenerator::bulidReadyHtml($preset, $baseImagePath, $outputPath, $sizeX, $sizeY, $type, $name);
						break;
					case "2":
						$external_id = reset($arParams['PROPERTY_VALUES'][$propsIds["PROP_THREE_CODE"]])['VALUE'];
						$result = $hlDataClass::getList(array(
							"select" => array("UF_CODE"),
							"order" => array(),
							"filter" => array("UF_XML_ID"=>$external_id),
						));
						if($res = $result->fetch())
						{    
							$code_three = $res["UF_CODE"];
						}

						$balans = CIBlockPropertyEnum::GetByID(reset($arParams['PROPERTY_VALUES'][$propsIds[$code_one]])['VALUE'])["VALUE"];
						$weight = CIBlockPropertyEnum::GetByID(reset($arParams['PROPERTY_VALUES'][$propsIds[$code_two]])['VALUE'])["VALUE"];
						$form = CIBlockPropertyEnum::GetByID(reset($arParams['PROPERTY_VALUES'][$propsIds[$code_three]])['VALUE'])["VALUE"];

						$success = ImageGenerator::bulidReadyHtml($preset, $baseImagePath, $outputPath, $sizeX, $sizeY, $balans, $weight, $form);
						break;
					case "3":
						$style = CIBlockPropertyEnum::GetByID(reset($arParams['PROPERTY_VALUES'][$propsIds[$code_one]])['VALUE'])["VALUE"];
						$level = "";
						foreach ($arParams['PROPERTY_VALUES'][$propsIds[$code_two]] as  $value) {
							$level .= CIBlockPropertyEnum::GetByID($value["VALUE"])["VALUE"] . "; ";
						}

						$success = ImageGenerator::bulidReadyHtml($preset, $baseImagePath, $outputPath, $sizeX, $sizeY, $style, $level);
						break;
				}

				if ($success === '1') {
					error_log("Файл успешно сохранён! \n", 3, LOG_FILE);
					CIBlockElement::SetPropertyValueCode($arParams['ID'], "GEN_IMAGES", Array ($propsIds["GEN_IMAGES"] => Array("VALUE"=>CFile::MakeFileArray($outputPath))));
					CIBlockElement::SetPropertyValueCode($arParams['ID'], "IMG_GEN", Array ($propsIds["IMG_GEN"] => Array("VALUE"=>"N")));
					unlink($outputPath);
				} elseif(is_string($success) && strpos($success, "Ошибка") !== false) {
					error_log($success . "\n", 3, LOG_FILE);
				} else {
					error_log("Ошибка загрузки \n", 3, LOG_FILE);
				}
			} else {
				error_log("Не найден файл по данному пути: " . $baseImagePath . "\n", 3, LOG_FILE);
			}

			self::$allowModify = true;
		}
	}
}

?>
