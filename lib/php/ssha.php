<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2016 Raytheon BBN Technologies
//
// Permission is hereby granted, free of charge, to any person obtaining
// a copy of this software and/or hardware specification (the "Work") to
// deal in the Work without restriction, including without limitation the
// rights to use, copy, modify, merge, publish, distribute, sublicense,
// and/or sell copies of the Work, and to permit persons to whom the Work
// is furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be
// included in all copies or substantial portions of the Work.
//
// THE WORK IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
// OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
// MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
// NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
// HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY
// WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE WORK OR THE USE OR OTHER DEALINGS
// IN THE WORK.
//----------------------------------------------------------------------

/**
 * A class to create a Salted SHA password hash.
 *
 * From http://ten-fingers-and-a-brain.com/2009/08/ssha-php/
 */
class SSHA
{

  public static function newSalt()
  {
    return chr(rand(0,255)).chr(rand(0,255)).chr(rand(0,255)).chr(rand(0,255));
  }

  public static function hash($pass,$salt)
  {
    return '{SSHA}'.base64_encode(sha1($pass.$salt,true).$salt);
  }

  public static function getSalt($hash)
  {
    return substr(base64_decode(substr($hash,-32)),-4);
  }

  public static function newHash($pass)
  {
    return self::hash($pass,self::newSalt());
  }

  public static function verifyPassword($pass,$hash)
  {
    return $hash == self::hash($pass,self::getSalt($hash));
  }

}

