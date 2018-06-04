
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>testovacia </title>
</head>
<body>
<div class="mautic-slot" data-slot-name="test"></div>
<div class="mautic-recombee" data-id="2" data-type="RecommendItemsToUser">content</div>

<!--
recombeeItemId
recombee
-->
    <?php
    $ar = [
        /*'AddCartAddition' => [
            0 => [
                'itemId' => 1,
                'amount' => 1,
                'price'  => 150,
            ],
            1 => [
                'itemId' => 1,
                'amount' => 1,
                'price'  => 150,
            ],
        ],*/
        'AddDetailView'   => [
            'itemId' => 1,
        ],
    ];
    ?>
<script>
    (function(w,d,t,u,n,a,m){w['MauticTrackingObject']=n;
        w[n]=w[n]||function(){(w[n].q=w[n].q||[]).push(arguments)},a=d.createElement(t),
            m=d.getElementsByTagName(t)[0];a.async=1;a.src=u;m.parentNode.insertBefore(a,m)
    })(window,document,'script','http://mauticlocal.fwd.wf/mautic/index_dev.php/mtc.js','mt');

    mt('send', 'pageview');
</script>
</body>
</html>