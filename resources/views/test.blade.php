<?php
/**
 * Created by PhpStorm.
 * User: PC
 * Date: 4/26/2019
 * Time: 4:26 PM
 */?>

<ul>
    @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
    @endforeach
</ul>
