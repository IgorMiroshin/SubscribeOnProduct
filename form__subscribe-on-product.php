<?php
header("Content-type: application/json; charset=utf-8");
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

class SubscribeOnProduct
{
    public string $email;
    public string $productID;

    public function __construct(string $email, string $productID)
    {
        $this->email = $email;
        $this->productID = $productID;
    }

    public function send(): array
    {
        $email = $this->email;
        $productID = $this->productID;

        if (!\Bitrix\Main\Loader::includeModule('catalog')) {
            return [];
        }

        $subscribeManager = new \Bitrix\Catalog\Product\SubscribeManager;

        $userID = '';

        $arFilter = ["EMAIL" => $email];
        $userGetList = CUser::GetList(($by = "id"), ($order = "desc"), $arFilter);
        if ($user = $userGetList->GetNext()) {
            $userID = $user["ID"];
        }

        $subscribeData = [
            'USER_CONTACT' => $email,
            'ITEM_ID' => $productID,
            'SITE_ID' => 's1',
            'CONTACT_TYPE' => \Bitrix\Catalog\SubscribeTable::CONTACT_TYPE_EMAIL,
            'USER_ID' => $userID ? $userID : false,
        ];

        $subscribeId = $subscribeManager->addSubscribe($subscribeData);

        if ($subscribeId) {
            $result["success"] = 'Благодарим за подписку на товар. Мы оповестим Вас при его поступлении.';
        } else {
            $errorObject = current($subscribeManager->getErrors());
            $errors = ['error' => true];
            if ($errorObject) {
                $errors['message'] = $errorObject->getMessage();
                if ($errorObject->getCode() == $subscribeManager::ERROR_ADD_SUBSCRIBE_ALREADY_EXISTS) {
                    $errors['setButton'] = true;
                }
            }
            $result["errors"] = $errors;
        }

        return $result;
    }
}

$result = [];

$email = $_POST["USER_EMAIL"];
$productID = $_POST["USER_PRODUCT_ID"];

if (!empty($email) && !empty($productID)) {
    $subscribeOnProduct = new SubscribeOnProduct($email, $productID);
    $result = $subscribeOnProduct->send();
} else {
    if (empty($productID)) {
        $result["errors"]["product"] = 'Не найден добавляемый товар!';
    }
    if (empty($email)) {
        $result["errors"]['email'] = 'Не указана почта пользователя!';
    }
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);