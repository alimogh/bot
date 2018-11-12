<?php
/**
 * Created by PhpStorm.
 * User: joeldg
 * Date: 4/13/17
 * Time: 6:26 PM
 */
namespace App\Controller;

use App\Controller\OHLC;
use Bowhead\Util\Util;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

trait CustomStrategies
{
    public function phuongb_bowhead_macd($pair, $data, $return_full = false, &$text = '')
    {
        $indicators = new Indicators();
        $rsi = $indicators->rsi($pair, $data, 14, $text); // 19 more accurate?
        $macd = trader_macd($data['close'], 12, 26, 9);
        $macd_raw = $macd[0];
        $signal = $macd[1];
        $hist = $macd[2];

        $macdCurrent = array_pop($macd_raw);
        $signalCurrent = array_pop($signal);
        $macd = $macdCurrent - $signalCurrent;

        // add 5 elements
        $histogram = $this->bowhead_5th_element($pair, $data, $return_full, $text);
        $text = 'macd: ' . $macd . ' signal ' . $signalCurrent . ' histogram: ' . $histogram;
        /** macd */
        if ($macd < 0 || $histogram < 0) {
            $return['side'] = 'short';
            $return['strategy'] = 'rsi_macd';

            return ($return_full ? $return : -1);
        }
        if ($macd > 0) {
            $return['side'] = 'long';
            $return['strategy'] = 'rsi_macd';

            return ($return_full ? $return : 1);
        }

        return 0;
    }

    public function phuongb_mfi($pair, $data, $return_full = false, &$text = '')
    {
        $indicators = new CustomIndicators();
        list($lastLastMfi, $lastMfi, $currentMfi) = $indicators->phuongMfis($pair, $data);
        if ($currentMfi > 85) {
            $text .= ' current Mfi: ' . (int) $currentMfi . ' ==> should sell ';
            return -1;
        }

        if ($currentMfi < 13) {
            $text .= ' current Mfi: ' . (int) $currentMfi . ' ==> should buy';
            return 1; // should buy
        }

        return 0;
    }

//    public function phuongb_vol($pair, $data, $return_full = false, &$text = '')
//    {
//        $indicators = new Indicators();
//        return $indicators->obv($pair, $data);
//        //        $indicators->mfi($pair, $data);
//    }

    public function phuongb_bowhead_sma($pair, $data, $return_full = false, &$text = '')
    {
        return $this->sma_maker($data['close'], 7);
    }

    public function phuongb_bowhead_stoch($pair, $data, $return_full = false, &$text = '')
    {
        $indicators = new Indicators();

        return $this->phuongbstoch($data, null, null, $text);
        //    if ($stoch < 0) {
        //      if ($adx == -1 && $bearish) {
        //        $return['side'] = 'short';
        //        $return['strategy'] = 'stoch_adx';
        //
        //        return ($return_full ? $return : -1);
        //        //    } elseif ($adx == 1 && $stoch > 0 && $bullish) {
        //      }
        //      elseif ($adx == 1 && $bullish) {
        //        $return['side'] = 'long';
        //        $return['strategy'] = 'stoch_adx';
        //
        //        return ($return_full ? $return : 1);
        //      }
        //    }
    }

    public function phuongbstoch($data = null, $matype1 = TRADER_MA_TYPE_SMA, $matype2 = TRADER_MA_TYPE_SMA, &$text = '')
    {
        if (empty($data['high'])) {
            return 0;
        }
        #$prev_close = $data['close'][count($data['close']) - 2]; // prior close
        #$current = $data['close'][count($data['close']) - 1];    // we assume this is current

        #high,low,close, fastk_period, slowk_period, slowk_matype, slowd_period, slowd_matype
        $stoch = trader_stoch($data['high'], $data['low'], $data['close'], 13, 3, $matype1, 3, $matype2);
        $slowk = $stoch[0];
        $slowd = $stoch[1];

        $slowk = array_pop($slowk); #$slowk[count($slowk) - 1];
        $slowd = array_pop($slowd); #$slowd[count($slowd) - 1];

        $text .= ' K: ' . $slowk;
        $text .= ' D: ' . $slowd;

        #echo "\n(SLOWK: $slowk SLOWD: $slowd)";
        # If either the slowk or slowd are less than 10, the pair is
        # 'oversold,' a long position is opened
        if ($slowk < 10 || $slowd < 10) {
            return 1;
            # If either the slowk or slowd are larger than 90, the pair is
            # 'overbought' and the position is closed.
        }
        elseif ($slowk > 80 || $slowd > 80) {
            return -1;
        }
        else {
            return 0;
        }
    }
}