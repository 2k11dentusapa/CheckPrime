<?php
bcscale(0);

/* ================== BCMATH UTIL ================== */

function bc_sqrt(string $n): string {
    if (bccomp($n, "2") < 0) return $n;

    $x = $n;
    $y = bcdiv(bcadd($x, "1"), "2");

    while (bccomp($y, $x) < 0) {
        $x = $y;
        $y = bcdiv(bcadd($x, bcdiv($n, $x)), "2");
    }
    return $x;
}

function isPerfectSquare(string $n): bool {
    $s = bc_sqrt($n);
    return bccomp(bcmul($s, $s), $n) === 0;
}

/* ================== PRIME NHỎ ================== */

$smallPrimes = [
  2,3,5,7,11,13,17,19,23,29,
  31,37,41,43,47,53,59,61,67,71,
  73,79,83,89,97,101,103,107,109,113,
  127,131,137,139,149,151,157,163,167,173,
  179,181,191,193,197,199
];

/* ================== WHEEL 210 ================== */

$wheelOffsets = [];
for ($i = 1; $i < 210; $i++) {
    if ($i % 2 && $i % 3 && $i % 5 && $i % 7) {
        $wheelOffsets[] = $i;
    }
}

/* ================== TRIAL DIVISION ================== */

function trialDivisionWheel210(string $n, array $smallPrimes, array $wheelOffsets): ?string {

    foreach ($smallPrimes as $p) {
        $bp = (string)$p;
        if (bccomp($n, $bp) === 0) return null;
        if (bcmod($n, $bp) === "0") return $bp;
    }

    $limit = bc_sqrt($n);
    $k = "1";

    while (true) {
        $base = bcmul($k, "210");
        if (bccomp($base, $limit) > 0) break;

        foreach ($wheelOffsets as $o) {
            $d = bcadd($base, (string)$o);
            if (bccomp($d, $limit) > 0) break;
            if (bcmod($n, $d) === "0") return $d;
        }
        $k = bcadd($k, "1");
    }
    return null;
}

/* ================== XỬ LÝ ================== */

$result = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $parts = [];
    for ($i = 1; $i <= 4; $i++) {
        $v = $_POST["part$i"] ?? "0";
        $v = preg_replace('/\D/', '', $v);
        $parts[] = str_pad($v, 6, "0", STR_PAD_LEFT);
    }

    $full = ltrim(implode('', $parts), '0');
    if ($full === '') $full = "0";
    $digits = strlen($full);

    $start = microtime(true);

    if (
        isPerfectSquare($full) ||
        isPerfectSquare(bcadd($full, "1")) ||
        isPerfectSquare(bcadd($full, "4"))
    ) {
        $result = [
            "prime" => false,
            "reason" => "Chính phương"
        ];
    } else {
        $d = trialDivisionWheel210($full, $smallPrimes, $wheelOffsets);
        $result = $d === null
            ? ["prime" => true]
            : ["prime" => false, "divisor" => $d];
    }

    $result["number"] = $full;
    $result["digits"] = $digits;
    $result["time"] = round(microtime(true) - $start, 3);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Kiểm tra số nguyên tố lớn (PHP)</title>
<style>
body {
    font-family: Arial;
    background: #f4f6f8;
    max-width: 900px;
    margin: auto;
    padding: 30px;
}
.container {
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 0 15px rgba(0,0,0,.1);
}
.input-group {
    display: flex;
    margin: 15px 0;
}
label {
    width: 120px;
    font-weight: bold;
}
input {
    width: 200px;
    font-size: 20px;
    padding: 10px;
    text-align: center;
    letter-spacing: 4px;
}
button {
    padding: 14px 30px;
    font-size: 20px;
    margin-top: 25px;
    cursor: pointer;
}
.result {
    margin-top: 30px;
    font-size: 22px;
    text-align: center;
}
.prime { color: #28a745; font-weight: bold; }
.not-prime { color: #dc3545; font-weight: bold; }
.divisor { font-size: 26px; color: #dc3545; }
</style>
</head>

<body>
<div class="container">
<h1 align="center">Kiểm tra số nguyên tố lớn<br><small>(PHP + BCMath)</small></h1>

<form method="post">
<?php for ($i = 1; $i <= 4; $i++): ?>
<div class="input-group">
    <label>Phần <?= $i ?>:</label>
    <input name="part<?= $i ?>" maxlength="6" placeholder="000000"
           value="<?= htmlspecialchars($_POST["part$i"] ?? "") ?>">
</div>
<?php endfor; ?>

<button type="submit">Kiểm tra</button>
</form>

<?php if ($result): ?>
<div class="result">
<p>Số: <b><?= $result["number"] ?></b> (<?= $result["digits"] ?> chữ số)</p>

<?php if ($result["prime"]): ?>
<p class="prime">→ Là số nguyên tố</p>
<?php else: ?>
<p class="not-prime">→ Không phải số nguyên tố</p>
<?php if (isset($result["divisor"])): ?>
<p class="divisor">Chia hết cho <?= $result["divisor"] ?></p>
<?php else: ?>
<p><?= $result["reason"] ?></p>
<?php endif; ?>
<?php endif; ?>

<p>Thời gian: <?= $result["time"] ?> giây</p>
</div>
<?php endif; ?>
</div>
</body>
</html>
