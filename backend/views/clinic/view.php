<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\Clinic */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Clinics', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="clinic-view">

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'name',
            'address',
            'tel',
            'chief',
            'idcard',
            'Business_license_img',
            'local_img',
            'doctor_certificate_img',
            'score',
//            'verify_status',
            [
                'attribute'=>'verify_status',
                'value' => $model->StatusStr,
            ],
            'verify_reason:ntext',
//            'user_uuid',
        ],
    ]) ?>

</div>
