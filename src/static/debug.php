<?php
declare(strict_types=1);
include '../includes/template/header.empty.html.php';
require '../../vendor/autoload.php';
use Ramsey\Uuid\Uuid;
use Base64Url\Base64Url;

// We generate a random fake prolific ID for testing, which we usually would get from Qualtrics
$uuid = Uuid::uuid4();
$prolific = Base64Url::encode('FAKE-Prolific-ID-'.substr($uuid->toString(),0,7));
?>
<div class="container">
    <div class="row">
        <div class="col-12">
            <h1>Debug Menu</h1>
                <?php echo '<a class="btn btn-lg btn-block btn-danger" href="dis.php?d1='.$prolific.'&d3='.urlencode(Base64Url::encode('control')).'" role="button">Biometric-Control</a>'; ?>
                <hr>
                <?php echo '<a class="btn btn-lg btn-block btn-success" href="dis.php?d1='.$prolific.'&d3='.urlencode(Base64Url::encode('webauthn-brands')).'" role="button">Biometric-Brands</a>'; ?>
                <?php echo '<a class="btn btn-lg btn-block btn-success" href="dis.php?d1='.$prolific.'&d3='.urlencode(Base64Url::encode('webauthn-hacked')).'" role="button">Biometric-Hacked</a>'; ?>
                <?php echo '<a class="btn btn-lg btn-block btn-success" href="dis.php?d1='.$prolific.'&d3='.urlencode(Base64Url::encode('webauthn-leaves')).'" role="button">Biometric-Leaves</a>'; ?>
                <?php echo '<a class="btn btn-lg btn-block btn-success" href="dis.php?d1='.$prolific.'&d3='.urlencode(Base64Url::encode('webauthn-stored')).'" role="button">Biometric-Stored</a>'; ?>
                <?php echo '<a class="btn btn-lg btn-block btn-success" href="dis.php?d1='.$prolific.'&d3='.urlencode(Base64Url::encode('webauthn-shared')).'" role="button">Biometric-Shared</a>'; ?>
                <hr>
                <?php echo '<a class="btn btn-lg btn-block btn-primary" href="dis.php?d1='.$prolific.'&d3='.urlencode(Base64Url::encode('fallback')).'" role="button">Non-biometric (PIN)</a>'; ?>
                <?php echo '<a class="btn btn-lg btn-block btn-secondary" href="dis.php?d1='.$prolific.'&d3='.urlencode(Base64Url::encode('password')).'" role="button">Password</a>'; ?>
            </div>
        </div>
    </div>
</div>
<script>Lockr.flush();</script>
<?php include '../includes/template/footer.html.php'; ?>