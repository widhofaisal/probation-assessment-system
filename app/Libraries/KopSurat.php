<?php

namespace App\Libraries;

/**
 * Letterhead (kop surat) assets for the printed evaluation form.
 *
 * The company logo is the exact bitmap embedded in
 * '27. FORM PENILAIAN PROBATION TEAM MEMBER.doc', extracted once and inlined
 * here as base64. It is inlined rather than referenced as a file so the DomPDF
 * renderer produces the same letterhead everywhere: DomPDF runs with
 * isRemoteEnabled=false, and the production host (InfinityFree) applies
 * open_basedir, so a URL or an absolute path would silently resolve to nothing
 * and leave the header without its logo.
 */
final class KopSurat
{
    /** Logo bitmap, 153x55 PNG with alpha — identical to the one in the .doc template. */
    private const LOGO_PNG_BASE64 = ''
        . 'iVBORw0KGgoAAAANSUhEUgAAAJkAAAA3CAYAAAAfSugkAAATA0lEQVR42u1cCXgT1fbH54IsgiBtkzTdaKHssigqoKAou5WlyUyS'
        . 'LildqRYLlIrgUhcQcQFUUPQ9EBXQ+tAHKurTB4KK+ESxKuhfQLHIIkuhmXtnMpNkzvvOnYQkBbpQ9F9gzvedr18zd+7cmfub31nu'
        . 'mdusmS666KKLLrrooosuuuiiiy666KKLLrrocsEKAFy812Jusd3SrLXrreeuElc/YxYWlXYlzxRd7bp39I3C1KE3SyW3DqlNsY0w'
        . 'feRg+lRBH+GFad2gfEmsUP5MxHZLROuKXs1aAZRdoj/p81QOVlS0OjxzuPE4F5Molt7S31U8ZKwrMzmbWKOn0Pzec2hBn5eErK6r'
        . 'Bd68wTUh4gvCR38r2GN3Cfa4vYIt7gCxxVYJfEw14WOO16bYRuBjjxN73EGK59pjdxPe/D32KViMm4Ss5DWuvN4vk7w+TxI+roTa'
        . 'E/OE4htTxem3DTzMdex8NKeXeee619oAwEX6rDVVNiovb+2ak9eFZHcbSvP7ZwvOzrOJLf5lktX1I5resYJYjZUCFy0Q3uRz8ybw'
        . 'WKPAZ40ElYsClUc1aIr/c5Hg4yLBa40ED9Ooeqp2jtd/vta3Ibxvf39uzgiEM6mUi6bEErWPOOJ2iFldNhBHxxUkLXGeUHhtIXEm'
        . 'jyAzU3rBu4vb6eD7i6V6yvD2R4tu7ObK7TvOldmphDg7r6KceQvlYyqJ1UgkPhrAbgLgIwG4CAYaLxcFMk6uJQokVGsUiJag0r9A'
        . 'T1zPql0fx4Jj8nBRDJQ4VuAjAOxGkG3RQKxGSeBjDhDO9DXNTHpbyOv5sJjbhz9WPKD3rpIBkToSziZLzS++0pXf/wYhu2e+kNZx'
        . 'MU1L2EI440FiNfoUq8YWwGsTxVjCEvmXAefPAiPeA96bj4ti94aKDEmtJgTfEWKP30YykpaSid2LhZxrhpAFOVE6UhrmiP8NZoyI'
        . 'lyf1s1RndF5EefMWwkUflZGh8IGjyeGiwG0NMhI9zzXAgnjPit+k47Pw2BB0JhfhzN+SjMSXSW53p1B8U9ed655priPpFHJ8+shE'
        . 'ktMzi2R2XkltMbuJxeDz+VmKgcpyYQCqISpZNPYGv28pWg1A+eh9JCPpHeJMLiZTbup5wftzVUvuaevK7ZNCM5JfoJx5p8RrvpTX'
        . 'z1Q6qBpoZv0+HqAfyhtB4KL3CY74lSS3t+Nw8XDjBQWuo1OHxdDsXoWiI2EjsRjkQPSFD0kHzNliOX9AwRtAsBhU6ojbJmT1mHWk'
        . '8Pqu5zW4xLLxZprVY5bAxWyXOSN742Sdsf4ShkM/zsMZgHLmSpKe9LRw58Du5xW4fi0eeyXN7VFA7bHfKJyB+RD1Zq3USKDjrwI6'
        . 'rl0NbQ90QsSp2+PvqX6teSz1FMdCf8M2px1LLe1q9h1oc7r+8Dje14QO4efg/4Hfa+37NPdYG7sxsGkROeWid9PMTrMO3zXQdM4D'
        . 'zJXf5waSlvC+aDGoDTOJBqDj2wO9vTU+EBAndgNxYldNs7uBmNmJAY3gcWwXeNjjOwAdeyXQO1Dbhk8yThz+hoptAueMbaf9z7Td'
        . 'KYBh0CY+9NzxISDAYyltgscDir+lXAFkTCutTWifnAmoIx6o1ehv7x8vviCOOO144BqB8d1RQ9lvbYDefoUflIZ6sxumR/CFJ7aY'
        . 'r1x39R97bqYitm69VMjtUSTypkqwnYG/hUxlNYL87F3g/epDUI/sA/Xw76Ae2gvqkf2gVv4EyjsvgDRjuDZRODnj2oP7oQmgvPci'
        . 'KG8/C8rKOSA6O/sB0gake4aB8tZCUNYuYn/Fgj5sQpXyJ1lfyrtLQP7HLKAYpYUyxLirQMzuAUr5E6CsWcTauh+2AE1py/pG4Cuv'
        . 'PATK2wtBWT0flNULQHnzKVBWPQbyC9NAKhmqjREZmIHmSnA/nQfeH78E7+f/0vpc+zwo/1oE3vWrwLf9C/B+9jaIuT1Z//Kiu0FZ'
        . '85zWJlTfeZ5dx/3gOA2QNYFcD2bD6J1aTdSVnvx41dy8tucMwPZOsbRwObvOlTiDF9mrwT7XhA4gWo3geX8p1CWqUAXeik/YJNPR'
        . 'LUB5fW7woCKDdOe1bFLJiEtBfumesHOlmaNAzEoG1S0Gf3SLIJXcorGDfzxkVHOQn5scdi5OLhnRnDGt/PcZtQ+SHAfvhteBTuyi'
        . 'sU9KG/YC1CXuJ7NZ/76ft9beUBRAee0RjfFCzWw9lS2z8UYgGYkrD5VZDE0eYDuLipoLGZ2fdVsNLGt9Js4qmsCak8oAdfwQ+PZs'
        . 'B5DFk44hi5Bhf2MP+4QIx0AqvEYD2cjLGKsEEUYYCyLTqdVHwvta85xm4ix+f9BiYGwa1mbVXCAjNZApbz4dPODzgSoJoIrCSWOU'
        . 'VzwKBMGL57z6cJ0gk5fOAjq6Jfh++AzqfttUkBdO0kyuJbLBz9ztZzVXWsd3hTlZEU0aZCSzU6mHN7AF4zOKiJjj2x68X7wTfIAe'
        . 'DzNBYtENzDS5708B5eUHmOnUwHcYpDv7AxlxGSgrZjcaZL69P4OY20ubsNtbg/u+MQCydHqQvT4veO6+XeCebQNp+lBm6tWqg8Fj'
        . 'O74E0REPZFSLcJCpKvi2bQDPv5eD5z8rwLN+JXjWr2KmH32vUJCp1AWejW+Cd8t7J497+2YQraZwf7HBQDMASUtaCsuclzdNJ7/4'
        . '5oHUavwDk6lnHHYjyCZ0AO9XHwQf7JF9IOb1BjK4GZDbLgJyUzMgQ5qxiVQP/AKeD5dr/hFO+FkAGWORZ4sYGBBonvdePJk5AyAb'
        . '0yocZDu/AdFmBjK0GZDhl4Dno1eDx379AcTMzkBGXR4GMgSONKmfdl/DLtF0+KUayK2mMJB5v/3EHyxcyV42fDYn+q/8CUR7XIN9'
        . 's1BVtJSSl+T3TWuKa44XkbROS8FmbFzeKwCyrSHmySOD77tNoPxzPsgLCkBeWAjue0eBaDGCVHwj86tYoIC+zlkCmffLdWwikdEC'
        . 'jHkSyIZdAvS2i0B5bXYIyLaBaI8FOvwSxrq+HV8Eb2P9Ki1AQWCGMplHAU/5EyDPTQf5qVyQ5+eD/HgmUFsMi3R934eA7L/vayAa'
        . 'cSnQUS3Au2l1CMh+bDTIUNFsCva4T6G8sHXTYrGycZ1Fe9xuzDI3LoEYyfwWjPhqFSqAumc7c/jF9EQtlD+LIEN2EQuvAfm5olNe'
        . 'Xnn1ERb9KSseBd8PnwfPqzrITLvnrWdA3VURBMCub0Esuk5LyeA46/DJ1GOHQMy7mr08YSD7bhOIaR1ZYCJNHQy+33aEA9xmbjTI'
        . 'ZGskSFy0LN6fwjctX6ygrwOcCSA2IDl4Wh3bjjFIKAvUJr7vPgUxq4tmhs4SyBjBfPQK+L7+6NQgW3ofuJ9wQn3Ft2MLSDNGnMid'
        . '1Qmy/buAZiYxwIQ5/hIB37b1LMURairZeNcs1tIkEyIavfCOFok4Os5pWkyW1TMLshJqz5g3RO9oC2JGEsgr5jAWwLyY6jp62knB'
        . 'SWPRZejkuapAKujbMJAJxxASYU75Ka+3/EGQSodpYzt2MCwFghEwsor6+86w8zG/h+yDY6kJMvXwXlD37ND0yH7wbl7jdx0i6hVd'
        . '+nZ+reX9WHTZyHVPDAAQZKnGpgUy8sCE4bIzSZRTO5wdkKVGall9fNB8NIhp8SDdMxzkp/NAXjSZRVfgVYJmZPNazUlf9/eQ/FQ1'
        . 'SJMHMFPK8mSLp9QJMu/HK5gDXZcwnwwDgwkdwlIYvl++BzG/tzb+jETwoNn3eoJss3YxG0uY4y8RcM8ardWN2eNYECBOHayZPcxh'
        . 'hYLM5wGQ6CkClbtYoHE2nj0WTcq8yUfvHlTYtBKwmze3qHYkvI9Oo3gWWAzBof76g/bwMFIb05LlrjBNgZEl+iuh5oK9+Zj6+OSN'
        . 'kAlRWWZdi/QuBs/bC+sEGZpbBEJYEFCxETyrFwK4STjIRlwGFE30qrnhvldGItAxLYEOvxjEvF6gHj0Q7GvrhywqRV8y6P9VaymY'
        . 'wVpEyiJoHPOoy9nqQ1h0uX0zSMWDwLN6QQ1TuUgzxRMa767gkpOLj9l99FE+punV3xf0doiWKEXhGneTBDP3y+7XJuDQXpCXlICY'
        . '04O92SyZOT4CZDyuyMGH/8VaoKMvB2Xl7HAz8st34F6QD8rSWaAe+i0sS44pENGZHA6y5WXgnjkKwE1PmEv3HDsLLtRjf9SdJ9td'
        . 'oS1l4TjHtGLLT6GJWXwJmLkMARn7fdNqcD+WznJs7jkOkOdlglR6G0tX+L7/NNjuqw+AjG7JgKzu2xUE6qFKxoAMaI0sEWJLTVld'
        . 'H2mSebJ1RSObC2kJr+EgpTO90XHtQEzvCL7/21rDEd4Nns/XsMiNvdk1k6PL7mMMoLxYWi9HXP3jN2aOEbxhIFv1GIvovN/8R2u3'
        . 'bxfQ9ESQ8nqziO+UIHvjiZBlrmPMdHvWvw7eT98C9eCek5aKkK1O6/irPk39rMeYrGJjEGRff6Qtno9uAZ4PloU/g3/M1KLXRvjF'
        . 'WDRK7PFf0rKUpludcWzGiHjiiN/CgHYmy0q3twbp7oEsyVrvyK1iE9C0BO0NL7yW5YvqWoJhgcLI5iAW9AVVdAXNzur5LIAIpC48'
        . 'H78KZHQrkAqvBZUcD07oG0+EgGxePVDtA887z7MlKsbUK2bXeQoDGebJQqJsXKdFHxVNqfuh1LDAAlmboqnGqpQzqDtjro7NvAeT'
        . '6k1+/VKYNrIbccRvxEHLDV1eQp/CFgPS5IHgnp/P3lyg1aeeBWSNj19jfg9bHPYzIYLU8+9XWHSn/lHpr96oZKyEa5BsjY8B+grm'
        . '2+HkqEf3M6bC5Sr0/2hWF/D9uh3kBZMY86Ap8u2qYP4VLmMpWK0xqqVW3fEIB+qBPaAe3qdVieD1Arr/F/B+vgbcT+dq1R2sbKct'
        . 'yC+Wan0FKktOaKXGmJigXfcS0LHtwbvpnyz/huaaJXT5aJYGwQoSz+a1rD2rTNn7s2ZiG2gy3f7vBCR77HZSNOjWc6YS49i0W+JE'
        . 'Z1K5zBvZ51z1Dwa0Mhv0vdgitdUE0vRbQV4yjU2M/OJ0kF8qBfn5qSBNG+KvEWsTlswNOMC4hHOiBg3VmaxNEJqUQLWC1chybKxG'
        . 'Lac70LT4E0WC4pSb2DHGDJwpvJ3D3w6vZzVox0Jr3kKvictAzJcMSZI64rW+TmrfGaSi68H9KM+SwayUKCMJxOzuTGlGUvgL6Yhj'
        . '42HXzu4GFFcbGmAutU/vDCCkJXxGJw/qd+7VlJWXtXZN7DlV5M37kNWUBi+aR54oCETzUFO1Ir8Op1+ewqWmmtW02F/YJESGtwvt'
        . 'D/sf7y8GTK2lXc1jJ1Xwdji5MoKN7zTtsRwIX4RA/VloZXDNbH5Y1XD7egPMbY3015IZiCuj07w/Zqac299uuooH3yCmJ5VLvEnC'
        . 'G9O/RPr/00C9v8SZgDgSNrgm9Uk5b+r8ywEuc93Zf5yUnriBWg0+8JcD6WD7a1TxMxfW+Yn45VJ+nwL8Ov+8/GIJyue2FfL7coI9'
        . 'bjXhTNWBLQZ0dvsTPouzatsbsKjRapIFe/xGmtMz73AZb2p2IcjWrUsuFe4acAtxdlpAbbE7hFSDCv7vL/XP5BpnDgN7aCBrUZt5'
        . 'D3F0XEbz+o3FF7zZhSpHy0aaSf41NpLRaSXlzbspZ/Lhxyeq/7tMSd+ioM7vKgOMxbalskbvJ2kd1wo5vSZVT7+tk771VA05/sjY'
        . 'RJLTyyZkJC2i9thtxGog+FEq8Np+D54LeAsD0V+Go/i/n2R+LRcFxGrwUHvsTpqeuIxO7JJbVTK0FwDouzvWRw6WDIgUJl072JXd'
        . 'o0R0JpcTPho3tKvGjeywFAX39PL5zavk3/tLPE/AJPp9KpmxVJS2f5nNAF57NIicSSR89I8U2Sqr+wPVef1GV91jidURcxbk0LxC'
        . 'g5DVc7CQe3UBSUtcQDOSPqA28y7KmQgCzxvyhiP4cHIU/55lUhPbtyzASoG9yLTN8AI7P2rBkMxMn8ktcKZKIaPTRsER/7yYf/VU'
        . 'kt1rBMzNi8VttnRU/NmR6tatLY9l94mrmjL0RuLs4sTP7sXs7isIZ9wk8OafBd58hKQa3LhbkM8ezXYxDOzACNbgLowKpy1/uf2T'
        . 'LlmCfmBDNHBeoB/sU/Ez0YndFBkjRbGx+OwmcNuicUMUWeDMVcQe8xvhjP+lzi6rSc7Vj1Fnl1wyZdCth9K6J13QTnuTBJ8z/vKj'
        . 'S2fFVJcO71/liB3jmtQvpzqv94MCF71I4GNWEGfyB0Jm8maSlrCDWAyVxGI8SmxmifBmhfLRXpE3AdtbljOAt56Kbd28EfBc7IPa'
        . 'zAr2STlTFUmN+p3Y438kzs5bSFbyOsLFLBc402Ka17uMTrouz5WWkCJOvmHA8SXTE396vPQK3VE/D2Tv3r0tXMvnXIV+jMtiSqb5'
        . '/a8R7hszhN47aoxQOixVKrre6S7om0+s5lJh3FWzXOMjZtamrI0lagbN7V5I77xuolByq5XeOyJFmDn65uq7B1134A5DN1zLhZVl'
        . 'HfDa+gzooosuuuiiiy666KKLLrrooosuujRZ+R9IaeTvk2nSSQAAAABJRU5ErkJggg==';

    /** Data URI suitable for a DomPDF <img src="...">. */
    public static function logoDataUri(): string
    {
        return 'data:image/png;base64,' . self::LOGO_PNG_BASE64;
    }
}
