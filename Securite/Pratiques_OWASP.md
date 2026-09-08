Top 10 OWASP selon cloudflare: https://www.cloudflare.com/fr-fr/learning/security/threats/owasp-top-10/

# 1. Contrôle d'accès rompu

Le contrôle d'accès désigne un système qui contrôle l'accès aux informations ou aux fonctionnalités. Les contrôles d'accès rompus permettent aux pirates de contourner l'autorisation et d'effectuer des tâches comme s'ils étaient des utilisateurs privilégiés tels que les administrateurs. Par exemple, une application web pourrait permettre à un utilisateur de modifier son compte en modifiant simplement une partie de son url, sans autre vérification.

Les contrôles d'accès peuvent être sécurisés en s'assurant qu'une application web utilise des jetons d'autorisation* et les soumette à des contrôles stricts.

*De nombreux services émettent des jetons d'autorisation lorsque les utilisateurs se connectent. Toute demande privilégiée faite par un utilisateur nécessite la présence du jeton d'autorisation. C'est un moyen sûr de vérifier que l'utilisateur est bien celui qu'il prétend être, sans avoir à saisir constamment ses identifiants de connexion.

# 2. Défauts de cryptographie

Si les applications web ne protègent pas les données sensibles telles que les informations financières et les mots de passe en utilisant le chiffrement, les acteurs malveillants peuvent accéder à ces données et les vendre ou les utiliser à des fins malveillantes. Ils peuvent également voler des informations sensibles en utilisant une attaque de l'homme du milieu.

Le risque d'exposition des données peut être limité par le chiffrement de toutes les données sensibles, l'authentification de toutes les transmissions et la désactivation de la mise* en cache de toute information sensible. En outre, les développeurs d'applications web doivent veiller à ne pas stocker inutilement des données sensibles.

*La mise en cache est la pratique consistant à stocker temporairement des données en vue de leur réutilisation. Par exemple, les navigateurs web mettent souvent en cache des pages web de sorte que si un utilisateur visite à nouveau ces pages dans un délai déterminé, le navigateur n'a pas besoin d'aller les chercher sur le web.

# 3. Injection

Les attaques par injection se produisent lorsque des données non fiables sont envoyées à un interpréteur de code par le biais du contenu d'un formulaire ou d'une autre soumission de données à une application web. Par exemple, un attaquant pourrait saisir du code de base de données SQL dans un formulaire qui attend un nom d'utilisateur en texte clair. Si les données de ce formulaire ne sont pas correctement sécurisées, le code SQL sera exécuté. C'est ce qu'on appelle une attaque par injection SQL.

La catégorie Injection comprend également les attaques de type Cross-site Scripting (XSS), qui étaient auparavant leur propre catégorie dans le rapport de 2017. Les stratégies d'atténuation pour le cross-site scripting consistent à éviter les requêtes HTTP non fiables, et à utiliser des plateformes de développement web modernes telles que ReactJS et Ruby on Rails, qui offrent une protection intégrée contre le cross-site scripting.

De manière générale, les attaques par injection peuvent être évitées avec la validation ou l'assainissement des données soumises par les utilisateurs. (La validation signifie le rejet des données suspectes, tandis que l'assainissement consiste à nettoyer les parties suspectes des données). En outre, un administrateur de base de données peut définir des contrôles pour réduire au minimum la quantité d'informations qu'une attaque par injection peut exposer.

En savoir plus sur la manière de prévenir les attaques par injection SQL.

# 4. Conception non sécurisée

Les risques liés à une conception non sécurisée comprennent toute une gamme de faiblesses que peut présenter l'architecture d'une application. Cette catégorie se concentre sur la conception d'une application, pas sur sa mise en œuvre. OWASP énumère les questions de sécurité utilisées (par exemple, « Dans quelle rue avez-vous grandi ? ») pour la récupération de mot de passe en guise d'exemple de flux de travail intrinsèquement non sécurisé. Quelle que soit la perfection de la mise en œuvre de ce flux de travail par ses développeurs, l'application restera vulnérable, car plusieurs personnes peuvent connaître la réponse à ces questions de sécurité.

La modélisation des menaces en amont du déploiement d'une application peut contribuer à atténuer ces types de vulnérabilités.

# 5. Mauvaise configuration de la sécurité

La mauvaise configuration de la sécurité est la vulnérabilité la plus courante sur la liste, et résulte souvent de l'utilisation de configurations par défaut ou de l'affichage d'erreurs excessivement verbeuses. Par exemple, une application peut présenter à l'utilisateur des erreurs trop descriptives qui peuvent révéler des vulnérabilités dans l'application. Il est possible d'atténuer ce problème en supprimant toute fonctionnalité inutilisée dans le code et en veillant à ce que les messages d'erreur soient plus généraux.

La catégorie Mauvaise configuration de la sécurité inclut l'attaque XML Externe Entities (XEE), qui était auparavant une catégorie en soi dans le rapport 2017. Il s'agit d'une attaque contre une application web qui analyse du contenu XML*. Ce contenu peut faire référence à une entité externe, dans une tentative d'exploiter une vulnérabilité dans l'analyseur. Une « entité externe » dans ce contexte fait référence à une unité de stockage telle qu'un disque dur. Un analyseur XML peut être utilisé pour envoyer des données à une entité externe non autorisée, transmettant ainsi des données sensibles directement à un acteur malveillant. Le meilleur moyen de prévenir les attaques XEE est de faire en sorte que les applications web acceptent un type de données moins complexe, comme JSON, ou tout au moins de corriger les analyseurs XML et de désactiver l'utilisation d'entités externes dans une application XML.

*XML ou Extensible Markup Language est un langage de balisage conçu pour être à la fois lisible par l'homme et par la machine. En raison de sa complexité et de ses vulnérabilités en matière de sécurité, il est en train d'être progressivement abandonné dans de nombreuses applications web.

# 6. Composants vulnérables et obsolètes

De nombreux développeurs web modernes utilisent des composants tels que des bibliothèques et des cadres dans leurs applications web. Ces composants sont des logiciels qui aident les développeurs à éviter le travail redondant et à fournir les fonctionnalités nécessaires ; des exemples courants sont les cadres de front-end comme React et les petites bibliothèques qui avaient l'habitude d'ajouter des icônes de partage ou des tests a/b. Certains pirates recherchent des vulnérabilités dans ces composants qu'ils peuvent ensuite utiliser pour orchestrer des attaques. Certains des composants les plus populaires sont utilisés sur des centaines de milliers de sites Web ; un pirate trouvant une faille de sécurité dans l'un de ces composants pourrait laisser des centaines de milliers de sites vulnérables à une exploitation.

Les développeurs de composants proposent souvent des correctifs de sécurité et des mises à jour pour remédier aux vulnérabilités connues, mais les développeurs d'applications web ne disposent pas toujours des versions les plus récentes ou des correctifs de composants qui s'exécutent sur leurs applications. Pour réduire au minimum le risque d'exécuter des composants présentant des vulnérabilités connues, les développeurs doivent supprimer les composants inutilisés de leurs projets, tout en s'assurant qu'ils reçoivent des composants d'une source fiable et qu'ils sont à jour.

# 7. Échecs de l’identification et de l’authentification

Les vulnérabilités des systèmes d'authentification (login) peuvent donner aux pirates l'accès à des comptes d'utilisateurs et même la possibilité de compromettre un système entier en utilisant un compte administrateur. Par exemple, un attaquant peut prendre une liste contenant des milliers de combinaisons connues de noms d'utilisateur/mots de passe obtenues lors d'une violation de données et utiliser un script pour essayer toutes ces combinaisons sur un système de connexion afin de voir si certaines fonctionnent.

Certaines stratégies visant à atténuer les vulnérabilités de l'authentification consistent à exiger une authentification à deux facteurs (2FA) ainsi qu'à limiter ou à retarder les tentatives de connexion répétées en utilisant la limitation du taux.

# 8. Défaillances des logiciels et de l’intégrité des données

Aujourd'hui, de nombreuses applications dépendent de plugins tiers et d'autres sources externes pour leur fonctionnement, et elles ne vérifient pas toujours que les mises à jour et les données provenant de ces sources n'ont pas été altérées et proviennent d'un emplacement attendu. Par exemple, une application qui accepte automatiquement les mises à jour d'une source externe pourrait être vulnérable à un acteur malveillant téléchargeant ses propres mises à jour malveillantes, qui seraient ensuite diffusées à toutes les installations de cette application. Cette catégorie comprend également les exploits de désérialisation non sécurisés : ces attaques sont le résultat de la désérialisation de données provenant de sources non fiables, et elles peuvent entraîner de graves conséquences comme des attaques DDoS et des attaques par exécution de code à distance.

Pour garantir que l'intégrité des données et des mises à jour n'a pas été compromise, les développeurs d'applications doivent utiliser des signatures numériques pour vérifier les mises à jour, contrôler leurs chaînes d'approvisionnement logicielles et faire en sorte que les pipelines d'intégration/déploiement continu (CI/CD) disposent d'un contrôle des accès fort et soient configurés correctement.

# 9. Défaillances de journalisation et de surveillance de la sécurité

De nombreuses applications web ne prennent pas suffisamment de mesures pour détecter les violations de données. Le délai moyen de découverte d'une violation est d'environ 200 jours après qu'elle se soit produite. Cela donne aux pirates beaucoup de temps pour causer des dommages avant qu'il n'y ait une riposte. L'OWASP recommande aux développeurs web de mettre en place un système de journalisation et de surveillance ainsi que des plans de réponse aux incidents afin de s'assurer qu'ils sont informés des attaques dont leurs applications font l'objet.

# 10. Falsification de requête côté serveur (Server-side Request Forgery)

La falsification de requêtes côté serveur (SSRF pour Server Side Request Forgery) est une attaque par laquelle un individu envoie une requête URL à un serveur afin de l'amener à récupérer une ressource inattendue, quand bien même cette ressource est par ailleurs protégée. Un acteur malveillant peut, par exemple, envoyer une requête pour www.example.com/super-secret-data/, même si les utilisateurs du web ne sont pas censés accéder à cet endroit, et ainsi, accéder à des données super secrètes issues de la réponse du serveur.

Il existe un certain nombre de mesures d'atténuation possibles pour les attaques SSRF, et l'une des plus importantes consiste à valider toutes les URL provenant des clients. Les URL non valides ne doivent pas entraîner de réponse directe et brute de la part du serveur.

Pour un regard plus technique et plus approfondi sur le Top 10 de l'OWASP, voir le rapport officiel.
