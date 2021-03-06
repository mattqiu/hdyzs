<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "pay".
 *
 * @property integer $pay_no
 * @property integer $appointment_no
 * @property string $type
 * @property integer $pay_time
 * @property integer $amount
 * @property integer $status
 * @property integer $created_at
 * @property integer $outdated_at
 *
 * @property Appointment $appointmentNo
 */
class Pay extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'pay';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['pay_no', 'appointment_no', 'amount'], 'required'],
            [['pay_no', 'appointment_no', 'pay_time', 'amount', 'status', 'created_at', 'outdated_at'], 'integer'],
            [['type'], 'string', 'max' => 10],
            [['appointment_no'], 'exist', 'skipOnError' => true, 'targetClass' => Appointment::className(), 'targetAttribute' => ['appointment_no' => 'appointment_no']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'pay_no' => 'Pay No',
            'appointment_no' => 'Appointment No',
            'type' => 'Type',
            'pay_time' => 'Pay Time',
            'amount' => 'Amount',
            'status' => 'Status',
            'created_at' => 'Created At',
            'outdated_at' => 'Outdated At',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAppointmentNo()
    {
        return $this->hasOne(Appointment::className(), ['appointment_no' => 'appointment_no']);
    }
}
