<?php

/*
 * (c) Jeroen van den Enden <info@endroid.nl>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace QrCode\src\Writer;

use QrCode\src\QrCodeInterface;

class BinaryWriter extends AbstractWriter
{
    /**
     * {@inheritdoc}
     */
    public function writeString(QrCodeInterface $qrCode)
    {
        $string = '
            0001010101
            0001010101
            1000101010
            0001010101
            0101010101
            0001010101
            0001010101
            0001010101
            0001010101
            1000101010
        ';

        return $string;
    }

    /**
     * {@inheritdoc}
     */
    public static function getContentType()
    {
        return 'text/plain';
    }

    /**
     * {@inheritdoc}
     */
    public static function getSupportedExtensions()
    {
        return ['bin', 'txt'];
    }
}
