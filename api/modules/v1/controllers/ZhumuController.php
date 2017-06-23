<?php
namespace api\modules\v1\controllers;

use yii;
use yii\rest\ActiveController;
use common\models\Zhumu;
use common\models\SystemConfig;
use common\models\AppointmentVideo;
use common\models\Appointment;

use yii\helpers\ArrayHelper;
use common\service\Service;


class ZhumuController extends ActiveController
{
    public $modelClass = 'common\models\Zhumu';//对应的数据模型处理控制器

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'authenticator' => [
                'optional' => [
                    'getmp3',
                ],
            ]
        ]);
    }

    /*
     * 接口1:从瞩目池中随机选取一个未被使用的
     * 包括（瞩目uuid、appkey、appsecret、username、password）
     */
    public function actionGetuser()
    {
        //当前用户
        $user = \yii::$app->user->identity;

        //只有专家才能发起
        if ($user->type != 1) {
            return Service::sendError(20401, '非法请求，只能专家才能发起会议');
        }

        $systemConfig = SystemConfig::findOne(['name' => 'zhumu_app_key']);
        if (isset($systemConfig)) {
            $app_key = $systemConfig['value'];
        }

        $systemConfig = SystemConfig::findOne(['name' => 'zhumu_app_secret']);
        if (isset($systemConfig)) {
            $app_secret = $systemConfig['value'];
        }

        //随机选择一个瞩目账号
        $maxId = Zhumu::find()->where(["status" => Zhumu::STATUS_ACTIVE])->max('id');
        $randId = rand(0, $maxId);

        $zhumu = Zhumu::find()->where(['>=', 'id', $randId])->andWhere(['status' => Zhumu::STATUS_ACTIVE])->one();
        $zhumuArray = $zhumu->attributes;

        unset($zhumuArray['id'], $zhumuArray['status'], $zhumuArray['create_at'], $zhumuArray['update_at']);
        $zhumuArray['app_key'] = $app_key;
        $zhumuArray['app_secret'] = $app_secret;
        return Service::sendSucc($zhumuArray);
    }

    /*
     * 接口2:回传会议信息（瞩目uuid+会议号+预约单号）
     */
    public function actionSetinfo()
    {
        //当前用户
        $user = \yii::$app->user->identity;

        $post = Yii::$app->request->post();

        //参数检查
        if (!isset($post['uuid']) || !isset($post['meeting_number']) || !isset($post['appointment_no'])) {
            return Service::sendError(10400, '缺少参数');
        }

        //只有专家才能发起
        if ($user->type != 1) {
            return Service::sendError(20401, '非法请求，只能专家才能发起会议');
        }

        //判断是否存在瞩目信息
        $zhumu = Zhumu::findOne(['uuid' => $post['uuid']]);
        if (empty($zhumu)) {
            return Service::sendError(20402, '没有这个账号信息');
        }

        //判断是否存在此预约单
        $appointment = Appointment::findone(['appointment_no' => $post['appointment_no']]);
        if (empty($appointment)) {
            return Service::sendError(20403, '没有此预约单');
        }

        $zhumu->status = Zhumu::STATUS_USED;
        $zhumu->save();

        $appointmentVideo = AppointmentVideo::find()
            ->where(['appointment_no' => $post['appointment_no']])
            ->andWhere(['zhumu_uuid' => $post['uuid']])
            ->andWhere(['meeting_number' => $post['meeting_number']])
            ->one();

        if (empty($appointmentVideo)) {
            $appointmentVideo = new AppointmentVideo();
            $appointmentVideo->appointment_no = $post['appointment_no'];
            $appointmentVideo->zhumu_uuid = $post['uuid'];
            $appointmentVideo->meeting_number = $post['meeting_number'];
//            $appointmentVideo->create_at = time();
            if (!$appointmentVideo->save()) {
                return Service::sendError(20404, '处理失败');
            }
        }
        return Service::sendSucc();
    }

    /*
     * 接口3:通过预约单号获取瞩目会议信息
     * 包括（appkey、appsecret、username、password、meeting room）
     */
    public function actionGetmeetingnumber($appointment_no)
    {
        $retData = [];
        $appointmentVideo = AppointmentVideo::findOne(['appointment_no' => $appointment_no]);
        if (!empty($appointmentVideo)) {
            $retData = $appointmentVideo->attributes;
            unset($retData['id'], $retData['status'], $retData['audio_url'], $retData['create_at'], $retData['zhumu_uuid']);

            $systemConfig = SystemConfig::findOne(['name' => 'zhumu_app_key']);
            if (isset($systemConfig)) {
                $retData['app_key'] = $systemConfig['value'];
            }

            $systemConfig = SystemConfig::findOne(['name' => 'zhumu_app_secret']);
            if (isset($systemConfig)) {
                $retData['app_secret'] = $systemConfig['value'];
            }

            //不需要用户名和密码
//            $zhumu = Zhumu::findOne(['uuid' => $appointmentVideo->zhumu_uuid]);
//            if (!empty($zhumu)) {
//                $retData['username'] = $zhumu->username;
//                $retData['password'] = $zhumu->password;
//            }
        }
        return Service::sendSucc($retData);
    }

    /*
     * 接口4:通知会议开始，回传预约单号。
     * 记录对应的预约单实际开始时间
     */
    public function actionStart()
    {
        //当前用户
        $user = \yii::$app->user->identity;

        $post = Yii::$app->request->post();

        //参数检查
        if (!isset($post['appointment_no'])) {
            return Service::sendError(10400, '缺少参数');
        }

        //只有诊所才能发起
        if ($user->type != 2) {
            return Service::sendError(20405, '非法请求，只能诊所才能调用');
        }

        //判断是否存在此预约单
        $appointment = Appointment::findone(['appointment_no' => $post['appointment_no']]);
        if (empty($appointment)) {
            return Service::sendError(20403, '没有此预约单');
        }

        //检查预约单状态，只有预约成功的才可以记录开始时间，并且只能记录一次。
        if ($appointment->status != 2) {
            return Service::sendError(20406, '只有预约成功的才可以开始');
        }

        if ($appointment->real_starttime != 0) {
            return Service::sendError(20407, '预约单已经开始了');
        }

        $appointment->real_starttime = time();
        if (!$appointment->save()) {
            return Service::sendError(20408, '预约单开始失败');
        }
        return Service::sendSucc();
    }

    /*
     * 接口5:通知会议结束、回传预约单号
     * 记录对应的预约单实际结束时间
     * 对应的瞩目账号状态改为正常
     */
    public function actionEnd()
    {
        //当前用户
        $user = \yii::$app->user->identity;

        $post = Yii::$app->request->post();

        //参数检查
        if (!isset($post['appointment_no'])) {
            return Service::sendError(10400, '缺少参数');
        }

        //只有诊所才能发起
        if ($user->type != 1) {
            return Service::sendError(20401, '非法请求，只能专家才能发起会议');
        }

        //判断是否存在此预约单
        $appointment = Appointment::findone(['appointment_no' => $post['appointment_no']]);
        if (empty($appointment)) {
            return Service::sendError(20403, '没有此预约单');
        }

        //检查预约单状态，只有预约成功的才可以记录开始时间，并且只能记录一次。
        if ($appointment->status != 2 || $appointment->real_starttime == 0) {
            return Service::sendError(20409, '只有预约成功并且已经开始的才可以结束');
        }

        if ($appointment->real_endtime != 0) {
            return Service::sendError(20410, '预约单已经结束了');
        }

        $appointment->real_endtime = time();
        if (!$appointment->save()) {
            return Service::sendError(20411, '预约单结束失败');
        }

        //对应的瞩目账号状态改为正常
        $appointmentVideo = AppointmentVideo::findAll(['appointment_no' => $post['appointment_no']]);
        if (!empty($appointmentVideo)) {
            foreach ($appointmentVideo as $k => $v) {
                $zhumu = Zhumu::findOne(['uuid' => $v->zhumu_uuid]);
                $zhumu->status = Zhumu::STATUS_ACTIVE;
                $zhumu->save();
            }
        }
        return Service::sendSucc();
    }

    /*
    * 接口6:访问mp3资源
    */
    public function actionGetmp3($appointment_no, $meeting_number)
    {
        $file = "/Users/damen/work/code/hdykt/zhumu/170614173547021730/1234/20170623144751.mp3";
        header('Content-Type:audio/mpeg');
        header('Content-Length:' . filesize($file));

        ob_start();
        $fp = fopen($file, 'r'); //文件
        while (!feof($fp)) {
            echo stream_get_line($fp, 65535, "\n");
        }
        ob_end_flush();
        exit;

//        echo file_get_contents($file);
    }
}  