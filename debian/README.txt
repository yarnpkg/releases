-----BEGIN PGP SIGNED MESSAGE-----
Hash: SHA512

Yarn has switched to a new signing key. See
https://github.com/yarnpkg/yarn/issues/9216 for more details.

Old key:
pub   rsa4096 2016-10-05
      72ECF46A56B4AD39C907BBB71646B01B86E50310
uid           Yarn Packaging <yarn@dan.cx>
sub   rsa4096
      DEE0F07B347CD8202105B20C23E7166788B63E1E


New key:
pub   ed25519 2026-01-28
      4EF8150F4F2D7DE44F1DFF0BB42879CC6B38E118
uid           Yarn Packaging (2026) <yarn@dan.cx>
sub   ed25519 2026-01-28
      FF7CB5667B542092084BBDC562D54FD4003F6525


I have cross-signed these keys to establish a chain of trust, and this message
has also been signed with both keys.

During the transition period, the public key at
http://dl.yarnpkg.com/debian/pubkey.gpg will contain both the old and the new
key. Eventually, the old key will be revoked and only the new key will be in
use.

I apologise for the inconvenience, but this was necessary in order to ensure
that our Debian repo continues to work properly.

2026-01-28
Daniel Lo Nigro
https://github.com/Daniel15
-----BEGIN PGP SIGNATURE-----

iHUEARYKAB0WIQT/fLVme1QgkghLvcVi1U/UAD9lJQUCaXp71QAKCRBi1U/UAD9l
JaXTAP9wYv9/Ax4h+6cjiidy6FssDGPS0cnjFg77JErp4+ezswEA6JvSzane2BEe
JACPYEp+Jd00kfpyajJ8424dYAsuvw+JAjMEAQEKAB0WIQRtmEkMbxrN3USORZVP
d2eTaUdbqgUCaXp71QAKCRBPd2eTaUdbqiRYD/9X6YKySEBVOLIk8WRwQePR3J4O
6luJuiHIjrUiekSPN0bFHU5fJs9AAWVQJR+G5svSQ6BSazgeZ41rdpGx5bzOu18M
AZMFXCf5l5VSmFpnT0eSwpfv7xYr6V3Eg+rTWavqeHdGGtK7X927hZx68Axxfbit
FeVJhbLJcfgHe7Uz/gxTqw08VcQYyqZBM8SzMRDbUudqvn1zdMRfHTRv/jTcPpym
VdHp4vCn+YWkf21DrvJxTsPeXfq/IYy20D0bDv/IdwfB1LE3QQKSqN/L9DGuaE9o
3IPjeeoGAXRrRcaW6nZ5VzubQi5YFt4RXx9B42CtUHyY8M94DF92fluEARiGEdG4
YBjLnT+F03V3/5Nz+D8TjtuCBc3ylwTHZe/oql5w4E8/Hy0nzLZmMb7ckaqLjRJC
EX4UAsCHxeyJZscNLjtA7EiuGPTs7lLWHsfeuFLIkAv0omdmplvDRp27/6WU91Sa
Fora5i78vK1GMYcgPtQjAwx7x5jnddbslF0izQFTWUnb0DwjQqHfEHYkKK7+iUcF
+/xcmAOdkNcYxdhzCpUKLZIKaU9g8hmGtJdhSI0715LcgQ6jPsXAx4AeENVTwJ2C
ShBaeLuNJ1mT+YmkSOVA4ZhqJU5CktmBQMVqeYdWGbDyXb4g8vJ/75iZ82qMsNu/
T1qU51oCIVddF9rf5g==
=6N9X
-----END PGP SIGNATURE-----
