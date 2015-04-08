<?php
/**
 * 2007-2014 [PagSeguro Internet Ltda.]
 *
 * NOTICE OF LICENSE
 *
 *Licensed under the Apache License, Version 2.0 (the "License");
 *you may not use this file except in compliance with the License.
 *You may obtain a copy of the License at
 *
 *http://www.apache.org/licenses/LICENSE-2.0
 *
 *Unless required by applicable law or agreed to in writing, software
 *distributed under the License is distributed on an "AS IS" BASIS,
 *WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *See the License for the specific language governing permissions and
 *limitations under the License.
 *
 *  @author    André da Silva Medeiros <andre@swdesign.net.br>
 *  @copyright 2007-2014 PagSeguro Internet Ltda.
 *  @license   http://www.apache.org/licenses/LICENSE-2.0
 */

/***
 * Encapsulates web service calls regarding PagSeguro payment requests
 */
class PagSeguroPreApprovalService
{

    /***
     *
     */
    const SERVICE_NAME = 'preApproval';
    /**
     * @var
     */
    private static $service;
    /**
     * @var
     */
    private static $connectionData;

    /***
     * @param PagSeguroConnectionData $connectionData
     * @return string
     */
    private static function buildCheckoutRequestUrl(PagSeguroConnectionData $connectionData)
    {
        return $connectionData->getServiceUrl();
    }

    /***
     * @param PagSeguroConnectionData $connectionData
     * @param $code
     * @return string
     */
    private static function buildCheckoutUrl(PagSeguroConnectionData $connectionData, $code)
    {
        return $connectionData->getPaymentUrl() . $connectionData->getResource('checkoutUrl') . "?code=$code";
    }

    /***
     * @param PagSeguroConnectionData $connectionData
     * @param $notificationCode
     * @return string
     */
    private static function buildFindByNotificationUrl(PagSeguroConnectionData $connectionData, $notificationCode)
    {
        $url = $connectionData->getWebserviceUrl() . $connectionData->getResource('findUrl') . 'notification';
        return "{$url}/{$notificationCode}/?" . $connectionData->getCredentialsUrlQuery();
    }

    /***
     * @param PagSeguroConnectionData $connectionData
     * @param $code
     * @return string
     */
    private static function buildFindByCodeUrl(PagSeguroConnectionData $connectionData, $code)
    {
        $url = $connectionData->getWebserviceUrl() . $connectionData->getResource('findUrl');
        return "{$url}{$code}/?" . $connectionData->getCredentialsUrlQuery();
    }

    /***
     * @param PagSeguroConnectionData $connectionData
     * @param $code
     * @return string
     */
    private static function buildFindByDayIntervalUrl(PagSeguroConnectionData $connectionData, $interval)
    {
        $url = $connectionData->getWebserviceUrl() . $connectionData->getResource('findUrl') . 'notifications';
        return "{$url}?" . $connectionData->getCredentialsUrlQuery() . "&interval=" . $interval;
    }

    /**
     * @param PagSeguroConnectionData $connectionData
     * @param array $params
     * @return string
     */
    private static function buildFindByDateIntervalUrl(PagSeguroConnectionData $connectionData, array $params)
    {
        $url = $connectionData->getWebserviceUrl() . $connectionData->getResource('findUrl');
        $initialDate = $params['initialDate'] != null ? $params['initialDate'] : "";
        $finalDate = $params['finalDate'] != null ? ("&finalDate=" . $params['finalDate']) : "";

        if ($params['pageNumber'] != null) {
            $page = "&page=" . $params['pageNumber'];
        }
        if ($params['maxPageResults'] != null) {
            $maxPageResults = "&maxPageResults=" . $params['maxPageResults'];
        }

        return "{$url}?" . $connectionData->getCredentialsUrlQuery() . "&initialDate={$initialDate}{$finalDate}
            {$page}{$maxPageResults}";
    }

    /***
     * @param PagSeguroConnectionData $connectionData
     * @param $code
     * @return string
     */
    private static function buildCancelUrl(PagSeguroConnectionData $connectionData, $code)
    {
        $credentialsArray = $connectionData->getCredentials()->getAttributesMap();
        return $connectionData->getWebserviceUrl() .
        $connectionData->getResource('cancelUrl') .
        "$code?".$connectionData->getCredentialsUrlQuery();
    }

    /**
     * @param $pageNumber
     * @param $maxPageResults
     * @param $initialDate
     * @param null $finalDate
     * @return array
     */
    private function buildParams($pageNumber, $maxPageResults, $initialDate, $finalDate = null)
    {
        $params = array(
            'initialDate' => PagSeguroHelper::formatDate($initialDate),
            'pageNumber' => $pageNumber,
            'maxPageResults' => $maxPageResults
        );

        $params['finalDate'] = $finalDate ? PagSeguroHelper::formatDate($finalDate) : null;

        return $params;
    }

    /**
     * @param PagSeguroCredentials $credentials
     * @param PagSeguroPaymentRequest $paymentRequest
     * @return null|PagSeguroParserData
     * @throws Exception
     * @throws PagSeguroServiceException
     */
    public static function createPreApprovalRequest(
        PagSeguroCredentials $credentials,
        PagSeguroPreApprovalRequest $request
    ) {

        LogPagSeguro::info("PagSeguroPreApprovalService.PreApprovalRequest(" .
            $request->toString() . ") - begin");

        self::$connectionData = new PagSeguroConnectionData($credentials, self::SERVICE_NAME);
        $data = array_merge(self::$connectionData->getCredentials()->getAttributesMap(),
            PagSeguroPreApprovalParser::getData($request));

        try {

            $connection = new PagSeguroHttpConnection();
            $connection->post(
                self::buildCheckoutRequestUrl(self::$connectionData),
                $data,
                self::$connectionData->getServiceTimeout(),
                self::$connectionData->getCharset()
            );

            self::$service = "PreApprovalRequest";
            return self::getResult($connection);

        } catch (PagSeguroServiceException $err) {
            //Logging
            LogPagSeguro::error("PagSeguroServiceException: " . $err->getMessage());
            //Exception
            throw $err;

        } catch (Exception $err) {
            //Logging
            LogPagSeguro::error("Exception: " . $err->getMessage());
            //Exception
            throw $err;
        }

    }

    /**
     * @param PagSeguroCredentials $credentials
     * @param $notificationCode
     * @return null|PagSeguroParserData
     * @throws Exception
     * @throws PagSeguroServiceException
     */
    public static function cancelPreApproval(PagSeguroCredentials $credentials, $notificationCode)
    {

        //Logging
        $log['text'] = "PagSeguroNotificationService.PreApprovalCancel($notificationCode) - begin";
        LogPagSeguro::info($log['text']);

        self::$connectionData = new PagSeguroConnectionData($credentials, self::SERVICE_NAME);

        try {

            $connection = new PagSeguroHttpConnection();
            $connection->get(
                self::buildCancelUrl(
                    self::$connectionData,
                    $notificationCode),
                self::$connectionData->getServiceTimeout(),
                self::$connectionData->getCharset()
            );

            self::$service = "PreApprovalCancel";
            return self::getResult($connection, $notificationCode);

        } catch (PagSeguroServiceException $err) {
            //Logging
            LogPagSeguro::error("PagSeguroServiceException: " . $err->getMessage());
            //Exception
            throw $err;

        } catch (Exception $err) {
            //Logging
            LogPagSeguro::error("Exception: " . $err->getMessage());
            //Exception
            throw $err;
        }
    }

    /**
     * @param PagSeguroCredentials $credentials
     * @param $code
     * @return null|PagSeguroParserData
     * @throws Exception
     * @throws PagSeguroServiceException
     */
    public static function findByCode(PagSeguroCredentials $credentials, $code)
    {

        LogPagSeguro::info("PagSeguroPreApprovalService.FindByCode($code) - begin");
        self::$connectionData = new PagSeguroConnectionData($credentials, self::SERVICE_NAME);

        try {

            $connection = new PagSeguroHttpConnection();
            $connection->get(
                self::buildFindByCodeUrl(self::$connectionData, $code),
                self::$connectionData->getServiceTimeout(),
                self::$connectionData->getCharset()
            );
            self::$service = "FindByCode";
            return self::getResult($connection, $code);

        } catch (PagSeguroServiceException $err) {
            //Logging
            LogPagSeguro::error("PagSeguroServiceException: " . $err->getMessage());
            //Exception
            throw $err;

        } catch (Exception $err) {
            //Logging
            LogPagSeguro::error("Exception: " . $err->getMessage());
            //Exception
            throw $err;
        }
    }

    /**
     * @param PagSeguroCredentials $credentials
     * @param $interval
     * @return null|PagSeguroParserData
     * @throws Exception
     * @throws PagSeguroServiceException
     */
    public static function findByDayInterval(PagSeguroCredentials $credentials, $interval)
    {

        LogPagSeguro::info("PagSeguroPreApprovalService.FindByDayInterval($interval) - begin");
        self::$connectionData = new PagSeguroConnectionData($credentials, self::SERVICE_NAME);

        try {

            $connection = new PagSeguroHttpConnection();
            $connection->get(
                self::buildFindByDayIntervalUrl(self::$connectionData, $interval),
                self::$connectionData->getServiceTimeout(),
                self::$connectionData->getCharset()
            );
            self::$service = "FindByDayInterval";
            return self::getResult($connection);

        } catch (PagSeguroServiceException $err) {
            //Logging
            LogPagSeguro::error("PagSeguroServiceException: " . $err->getMessage());
            //Exception
            throw $err;

        } catch (Exception $err) {
            //Logging
            LogPagSeguro::error("Exception: " . $err->getMessage());
            //Exception
            throw $err;
        }
    }

    /**
     * @param PagSeguroCredentials $credentials
     * @param $pageNumber
     * @param $maxPageResults
     * @param $initialDate
     * @param null $finalDate
     * @return null|PagSeguroParserData
     * @throws Exception
     * @throws PagSeguroServiceException
     */
    public static function findByDateInterval(
        PagSeguroCredentials $credentials,
        $pageNumber,
        $maxPageResults,
        $initialDate,
        $finalDate = null)
    {
        //Logging
        $log['text'] = "PagSeguroPreApprovalService.FindByDateInterval(initialDate="
            . PagSeguroHelper::formatDate($initialDate) . ", finalDate=" . PagSeguroHelper::formatDate($finalDate) .
            "begin";
        LogPagSeguro::info($log['text']);

        self::$connectionData = new PagSeguroConnectionData($credentials, self::SERVICE_NAME);

        $params = self::buildParams($pageNumber, $maxPageResults, $initialDate, $finalDate);

        try {

            $connection = new PagSeguroHttpConnection();
            $connection->get(
                self::buildFindByDateIntervalUrl(self::$connectionData, $params),
                self::$connectionData->getServiceTimeout(),
                self::$connectionData->getCharset()
            );
            self::$service = "FindByDateInterval";
            return self::getResult($connection);

        } catch (PagSeguroServiceException $err) {
            //Logging
            LogPagSeguro::error("PagSeguroServiceException: " . $err->getMessage());
            //Exception
            throw $err;

        } catch (Exception $err) {
            //Logging
            LogPagSeguro::error("Exception: " . $err->getMessage());
            //Exception
            throw $err;
        }
    }

    /**
     * @param PagSeguroCredentials $credentials
     * @param $notificationCode
     * @return null|PagSeguroParserData
     * @throws Exception
     * @throws PagSeguroServiceException
     */
    public static function findByNotification(PagSeguroCredentials $credentials, $notificationCode)
    {

        LogPagSeguro::info("PagSeguroPreApprovalService.FindByNotification($notificationCode) - begin");
        self::$connectionData = new PagSeguroConnectionData($credentials, self::SERVICE_NAME);

        try {

            $connection = new PagSeguroHttpConnection();
            $connection->get(
                self::buildFindByNotificationUrl(self::$connectionData, $notificationCode),
                self::$connectionData->getServiceTimeout(),
                self::$connectionData->getCharset()
            );
            self::$service = "FindByNotification";
            return self::getResult($connection, $notificationCode);

        } catch (PagSeguroServiceException $err) {
            //Logging
            LogPagSeguro::error("PagSeguroServiceException: " . $err->getMessage());
            //Exception
            throw $err;

        } catch (Exception $err) {
            //Logging
            LogPagSeguro::error("Exception: " . $err->getMessage());
            //Exception
            throw $err;
        }
    }

    /**
     * @param $connection
     * @param null $code
     * @return null|PagSeguroParserData
     * @throws PagSeguroServiceException
     */
    private function getResult($connection, $code = null)
    {

        $httpStatus = new PagSeguroHttpStatus($connection->getStatus());
        $response   = $connection->getResponse();

        switch ($httpStatus->getType()) {

            case 'OK':

                switch(self::$service) {
                    case "PreApprovalRequest":

                        $response = PagSeguroPreApprovalParser::readSuccessXml($response);

                        $result = array ( 'code' => $response->getCode(),
                            'cancelUrl' => self::buildCancelUrl(self::$connectionData,
                                $response->getCode()),
                            'checkoutUrl' => self::buildCheckoutUrl(self::$connectionData,
                                $response->getCode()) );

                        break;

                    case "PreApprovalCancel":
                        $result = PagSeguroPreApprovalParser::readCancelXml($response);
                        break;

                    case "FindByCode":
                        $result = PagSeguroPreApprovalParser::readPreApproval($response);
                        break;

                    case "FindByNotification":
                        $result = PagSeguroPreApprovalParser::readPreApproval($response);
                        break;

                    case "FindByDayInterval":
                        $result = PagSeguroPreApprovalParser::readSearchResult($response);
                        break;

                    case "FindByDateInterval":
                        $result = PagSeguroPreApprovalParser::readSearchResult($response);
                        break;
                }

                //Logging
                if (is_null($code) && self::$service == "PreApprovalRequest") {
                    $log['text'] = sprintf("PagSeguroPreApprovalService.%s(".$response->toString().") - end ",
                        self::$service);
                    LogPagSeguro::info($log['text'] . ")");
                } else {
                    $log['text'] = sprintf("PagSeguroPreApprovalService.%s($code) - end ", self::$service);
                    LogPagSeguro::info($log['text']);
                }

                break;

            case 'BAD_REQUEST':

                $errors = PagSeguroServiceParser::readErrors($response);
                $errors = new PagSeguroServiceException($httpStatus, $errors);

                //Logging
                $log['text']  = sprintf("PagSeguroPreApprovalService.%s($code) - error ", self::$service);
                LogPagSeguro::error($log['text'] . $errors->getOneLineMessage());

                //Exception
                throw $errors;

                break;

            default:

                $errors = new PagSeguroServiceException($httpStatus);

                //Logging
                $log['text'] = sprintf("PagSeguroPreApprovalService.%s($code) - error ", self::$service);
                LogPagSeguro::error($log['text'] . $errors->getOneLineMessage());

                //Exception
                throw $errors;

                break;
        }
        return isset($result) ? $result : null;
    }
}
