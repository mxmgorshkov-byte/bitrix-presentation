<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;

/**
 * Author: Maxim Gorshkov <maxim.y.gorshkov@gmail.com> 
 * This class updates product properties
**/

class UpdaterForOzon extends \CBitrixComponent
{
    const CATALOG_ID = 10; 
    const OFFERS_CATALOG_ID = 11; 
    
    const CURRENT_WEIGHT = 300;
    const CURRENT_WIDTH = 270;
    const CURRENT_LENGHT = 425;
    const CURRENT_HEIGHT = 10;

    /**
     * This function combines the functionality of the others
     * @param int ELEMENT_ID
    **/
    public function UpdateElemets($element_id = "") {
        $res = $this->GetActiveElements(self::CATALOG_ID, $element_id);
        $data = $this->GetIdsAndArticles($res);
        $arOffers = $this->GetArOffers($data["IDS"]);
        
        foreach($arOffers as $item) {
            foreach($item as $offer_id=>$offer) {
                $this->UpdateOfferParameters($offer["PARENT_ID"]);
                $this->UpdateVat($offer["PARENT_ID"]);

                $offer_property = $this->GetActiveElements(self::OFFERS_CATALOG_ID, $offer_id);
                $this->UpdateArticles($offer_property, $offer, $offer_id, self::OFFERS_CATALOG_ID, $data);
                
                $this->UpdateOfferParameters($offer_id);
                
                $this->UpdateVat($offer_id);
            }
        }
    }

    /**
     * Get list of iblock elements
     * @param int IBLOCK_ID; int ELEMENT_ID
     * @return void 
    **/
    private function GetActiveElements($iblock_id, $id) {
        $id_query = (!empty($id))? ["ID" => $id] : [];
        $arFilter = [
            "IBLOCK_ID" => $iblock_id, 
            "ACTIVE_DATE" => "Y", 
            "ACTIVE" => "Y",
            $id_query
        ];
        $res = CIBlockElement::GetList(Array(), $arFilter, false);

        return $res;
    }

    /**
     * Get list of elements ids and articles
     * @param void
     * @return array
    **/
    private function GetIdsAndArticles($res) {
        $data = array();
        $articles = array();
        $ids = array();
        while($ob = $res->GetNextElement())
        {
            $arItem = (array)$ob;
            $arProps = $ob->GetProperties();
            $articles[$arItem["fields"]["ID"]] = $arProps["CML2_ARTICLE"]["VALUE"];
            $ids[] = $arItem["fields"]["ID"];
        }

        $data["ARTICLES"] = $articles;
        $data["IDS"] = $ids;

        return $data;
    }

    /**
     * Get list of offers for elements
     * @param array ELEMENTS_ID
     * @return void
    **/
    private function GetArOffers($ids) {
        $arOffers = CCatalogSKU::getOffersList(
            $ids, 
            self::CATALOG_ID, 
        );

        return $arOffers;
    }

    /**
     * Update articles of element
     * @param void ; array OFFER_PARAMETRS; int OFFER_ID; int IBLOCK ID; array ARRAY_OF_ARTICLES
    **/
    private function UpdateArticles($offer_property, $offer, $offer_id, $iblock_id, $data) {
        if($ob = $offer_property->GetNextElement())
        {
            $arProps = $ob->GetProperties();
            $property_code = "CML2_ARTICLE";
            $property_value = $data["ARTICLES"][$offer["PARENT_ID"]]."/".$arProps["SIZE_CLOTHES"]["VALUE"];
            CIBlockElement::SetPropertyValuesEx($offer_id, $iblock_id, array($property_code => $property_value), array("DoNotValidateLists"));
        }
    }

    /**
     * Update parametrs of offer
     * @param int OFFER_ID
    **/
    private function UpdateOfferParameters($offer_id) {
        $child_offer = CCatalogProduct::GetByID($offer_id);
        if(empty($child_offer["WEIGHT"])) {
            $arFields = [ 
                "WEIGHT" => self::CURRENT_WEIGHT
            ];
            CCatalogProduct::Update($offer_id, $arFields);
        }
        if(empty($child_offer["LENGTH"])) {
            $arFields = [ 
                "LENGTH" => self::CURRENT_LENGHT
            ];
            CCatalogProduct::Update($offer_id, $arFields);
        }
        if(empty($child_offer["WIDTH"])) {
            $arFields = [ 
                "WIDTH" => self::CURRENT_WIDTH
            ];
            CCatalogProduct::Update($offer_id, $arFields);
        }
        if(empty($child_offer["HEIGHT"])) {
            $arFields = [
                "HEIGHT" => self::CURRENT_HEIGHT
            ];
            CCatalogProduct::Update($offer_id, $arFields);
        }
    }

    /**
     * Update VAT of offer
     * @param int OFFER_ID
    **/
    private function UpdateVat($offer_id) {
        $res = [
            "VAT_INCLUDED" => 'Y',
            "VAT_ID" => 1
        ];    
        CCatalogProduct::Update($offer_id,$res);
    }
}
