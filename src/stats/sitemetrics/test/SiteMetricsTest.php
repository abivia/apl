<?php
/**
 * Test page
 * 
 * @package AP5L
 * @subpackage Stats.SiteMetrics
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: SiteMetricsTest.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

require_once('stats/sma/SiteMetrics.php');

$metrics = new SiteMetrics();

$metrics -> pageStart('testpage');
$metrics -> pageAttribute('referrer', 'somewhere');
$metrics -> pageAttribute('multi', 'value1');
$metrics -> pageAttribute('multi', 'value2');

echo 'This page isn\'t doing much!<br/>';
for ($ind = 0; $ind <= 42; ++$ind) {
    // foo
}

$metrics -> pageEnd();

echo htmlentities($metrics -> _page -> toXml());
?>