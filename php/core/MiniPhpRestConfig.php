<?php

namespace MiniPhpRest\core;

use MiniPhpRest\core\config\MiniPhpRestConfigLogger;
use Psr\Log\LoggerInterface;

/**
 * Objet central de configuration du framework MiniPhpRest.
 *
 * Cette classe regroupe tous les paramètres nécessaires au bootstrap et à
 * l'exécution du framework : mode debug, résolution des classes applicatives,
 * découpage de l'URI, racine serveur, configuration des logs, callbacks de
 * cycle de vie, dépendances injectables et contexte d'exécution partagé.
 *
 * L'idée est de construire une instance de configuration au démarrage de
 * l'application, puis de la passer au runner / au contrôleur principal afin de
 * piloter le comportement du framework de manière centralisée.
 */
class MiniPhpRestConfig
{

    /**
     * Indique si le framework doit fonctionner en mode debug.
     *
     * Quand cette valeur est activée, le framework peut exposer davantage
     * d'informations de diagnostic, rendre les logs plus verbeux et faciliter
     * le débogage pendant le développement. En production, cette option doit
     * généralement rester désactivée.
     *
     * @var bool
     */
    private bool $isDebug = false;

    /**
     * Représentation normalisée des paramètres serveur de la requête courante.
     *
     * L'objet encapsule les informations HTTP essentielles lues depuis
     * `$_SERVER` au moment de l'instanciation : méthode HTTP et URI demandée.
     * Il permet au framework d'accéder à ces données sans manipuler directement
     * les variables globales dans le reste du code.
     *
     * @var ServerParamObject
     */
    private ServerParamObject $serverParamObject;

    /**
     * Table de correspondance entre un préfixe logique et un dossier de code.
     *
     * La clé représente généralement un nom de zone, de module ou de base de
     * classes, et la valeur indique le répertoire racine où le framework doit
     * rechercher les classes applicatives correspondantes. La valeur par défaut
     * permet de relier le bloc "Base" au dossier `php`.
     *
     * @var array<string, string>
     */
    private array $appClassFolders = [
        'Base' => 'php'
    ];

    /**
     * Décalage utilisé lors de l'analyse de l'URI pour extraire la route.
     *
     * Le framework ignore un certain nombre de segments du chemin avant de
     * déterminer l'action ou le contrôleur cible. Ce réglage est utile lorsque
     * l'application est servie depuis un sous-répertoire, derrière un front
     * controller, ou avec une structure d'URL spécifique.
     */
    private int $uriSliceOffset = 2;

    /**
     * Chemin racine du serveur ou de l'application.
     *
     * Ce chemin sert de base pour résoudre les fichiers du projet : classes,
     * ressources, fichiers de log, configuration ou tout autre élément dont le
     * framework a besoin pour fonctionner correctement.
     */
    private string $serverRootPath = '';

    /**
     * Configuration dédiée aux journaux du framework et de l'application.
     *
     * Cet objet regroupe les chemins de fichiers de log ainsi que les niveaux
     * minimaux de journalisation à appliquer. Il permet de séparer clairement
     * les logs internes du framework de ceux produits par le code applicatif.
     */
    private MiniPhpRestConfigLogger $loggerConfig;

    /**
     * Callback optionnel exécuté avant la phase de routage.
     *
     * Ce point d'extension permet d'intervenir très tôt dans le cycle de vie du
     * framework, avant la résolution finale de la route. On peut y initialiser
     * des éléments de contexte, préparer le logger, ajuster dynamiquement la
     * configuration ou effectuer des vérifications préalables.
     *
     * Signature attendue :
     * - MiniPhpRestConfig $config : configuration en cours d'utilisation
     * - FrameworkContext $context : contexte d'exécution partagé
     * - LoggerInterface $logger : logger principal utilisable pendant le bootstrap
     *
     * @var ?callable(MiniPhpRestConfig, FrameworkContext, LoggerInterface):void
     */
    private $earlyStageFunction = null;

    /**
     * Callback optionnel exécuté juste avant l'initialisation du contrôleur.
     *
     * Ce hook s'exécute après la phase de routage, mais avant la création ou
     * l'initialisation du contrôleur cible. Il est utile pour appliquer des
     * règles transverses : contrôle d'accès, enrichissement du contexte,
     * préparation de dépendances spécifiques ou court-circuitage du flux si
     * une condition métier l'exige.
     *
     * @var ?callable(MiniPhpRestConfig, FrameworkContext, LoggerInterface):void
     */
    private $beforeInitializeControllerFunction = null;

    /**
     * Callback optionnelle exécuté avant le renvoie des données en cas d'erreur
     *
     * Permet de transformer la réponse renvoyée en cas d'erreur.
     *
     *
     * @var ?callable(array, \Exception $ex, LoggerInterface $logger) : array
     */
    private $transformDataOnErrorFunction = null;

    /**
     * Registre des dépendances explicites de l'application.
     *
     * Le tableau associe une classe ou une interface demandée à son implémenta-
     * tion concrète. Le framework peut s'appuyer sur cette table pour résoudre
     * ses dépendances lors de l'instanciation des contrôleurs ou des services.
     *
     * @var array<string, string>
     */
    private array $dependencies = [];

    /**
     * Contexte d'exécution partagé par le framework.
     *
     * Cet objet agit comme un conteneur léger pour les éléments communs à tout
     * le cycle de requête : connexions PDO, propriétés applicatives, dépendances
     * déjà résolues, etc. Il peut être injecté par l'application pour remplacer
     * une implémentation par défaut ou pour enrichir le comportement du runtime.
     */
    private ?FrameworkContext $frameworkContextImpl = null;



    /**
     * Construit une configuration initiale à partir de l'environnement courant.
     *
     * À l'instanciation, les paramètres serveur sont capturés depuis les globals
     * PHP et la configuration de logs reçoit ses valeurs par défaut.
     */
    public function __construct()
    {
        $this->serverParamObject = ServerParamObject::fromGlobals();

        $this->loggerConfig = new MiniPhpRestConfigLogger();

    }


    /**
     * Retourne les correspondances utilisées pour résoudre les classes de
     * l'application vers leurs dossiers sources.
     *
     * @return array<string, string>
     */
    public function getAppClassFolders(): array
    {
        return $this->appClassFolders;
    }

    /**
     * Définit les correspondances de dossiers pour la résolution des classes.
     *
     * Cette méthode permet de surcharger la structure par défaut lorsque
     * l'application utilise plusieurs zones, modules ou répertoires sources.
     *
     * @param array<string, string> $appClassFolders
     * @return MiniPhpRestConfig
     */
    public function setAppClassFolders(array $appClassFolders): MiniPhpRestConfig
    {
        $this->appClassFolders = $appClassFolders;
        return $this;
    }

    /**
     * Indique si le framework fonctionne en mode debug.
     *
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->isDebug;
    }

    /**
     * Active ou désactive le mode debug du framework.
     *
     * @param bool $isDebug
     * @return MiniPhpRestConfig
     */
    public function setIsDebug(bool $isDebug): MiniPhpRestConfig
    {
        $this->isDebug = $isDebug;
        return $this;
    }

    /**
     * Retourne le chemin racine utilisé pour résoudre les ressources du projet.
     *
     * @return string
     */
    public function getServerRootPath(): string
    {
        return $this->serverRootPath;
    }

    /**
     * Définit le chemin racine de l'application ou du serveur.
     *
     * @param string $serverRootPath
     * @return MiniPhpRestConfig
     */
    public function setServerRootPath(string $serverRootPath): MiniPhpRestConfig
    {
        $this->serverRootPath = $serverRootPath;
        return $this;
    }

    /**
     * Retourne le nombre de segments d'URI ignorés par l'analyse de routage.
     *
     * @return int
     */
    public function getUriSliceOffset(): int
    {
        return $this->uriSliceOffset;
    }

    /**
     * Définit le nombre de segments d'URI à ignorer avant d'extraire la route.
     *
     * @param int $uriSliceOffset
     * @return MiniPhpRestConfig
     */
    public function setUriSliceOffset(int $uriSliceOffset): MiniPhpRestConfig
    {
        $this->uriSliceOffset = $uriSliceOffset;
        return $this;
    }

    /**
     * Retourne l'objet qui encapsule les paramètres serveur de la requête.
     *
     * @return ServerParamObject
     */
    public function getServerParamObject(): ServerParamObject
    {
        return $this->serverParamObject;
    }

    /**
     * Remplace l'objet des paramètres serveur par une autre instance.
     *
     * Cette méthode permet notamment de simuler une requête en test ou de
     * fournir une représentation des paramètres serveur construite manuellement.
     *
     * @param ServerParamObject $serverParamObject
     * @return MiniPhpRestConfig
     */
    public function setServerParamObject(ServerParamObject $serverParamObject): MiniPhpRestConfig
    {
        $this->serverParamObject = $serverParamObject;
        return $this;
    }

    /**
     * Retourne la configuration de journalisation du framework et de l'app.
     *
     * @return MiniPhpRestConfigLogger
     */
    public function getLoggerConfig(): MiniPhpRestConfigLogger
    {
        return $this->loggerConfig;
    }

    /**
     * Remplace la configuration de logs utilisée par le framework.
     *
     * @param MiniPhpRestConfigLogger $loggerConfig
     * @return MiniPhpRestConfig
     */
    public function setLoggerConfig(MiniPhpRestConfigLogger $loggerConfig): MiniPhpRestConfig
    {
        $this->loggerConfig = $loggerConfig;
        return $this;
    }

    /**
     * Retourne le callback exécuté avant la phase de routage.
     *
     * @return null|callable(MiniPhpRestConfig, FrameworkContext, LoggerInterface):void
     */
    public function getEarlyStageFunction(): ?callable
    {
        return $this->earlyStageFunction;
    }

    /**
     * Définit le callback exécuté avant la phase de routage.
     *
     * @param null|callable(MiniPhpRestConfig, FrameworkContext, LoggerInterface):void $earlyStageFunction
     * @return MiniPhpRestConfig
     */
    public function setEarlyStageFunction(?callable $earlyStageFunction): MiniPhpRestConfig
    {
        $this->earlyStageFunction = $earlyStageFunction;
        return $this;
    }

    /**
     * Retourne le callback exécuté juste avant l'initialisation du contrôleur.
     *
     * @return null|callable(MiniPhpRestConfig, FrameworkContext, LoggerInterface):void
     */
    public function getBeforeInitializeControllerFunction(): ?callable
    {
        return $this->beforeInitializeControllerFunction;
    }

    /**
     * Définit le callback exécuté juste avant l'initialisation du contrôleur.
     *
     * @param null|callable(MiniPhpRestConfig, FrameworkContext, LoggerInterface):void $beforeInitializeControllerFunction
     * @return MiniPhpRestConfig
     */
    public function setBeforeInitializeControllerFunction(?callable $beforeInitializeControllerFunction): MiniPhpRestConfig
    {
        $this->beforeInitializeControllerFunction = $beforeInitializeControllerFunction;
        return $this;
    }

    /**
     * Enregistre une correspondance entre une abstraction et son implémentation.
     *
     * Le framework utilisera ensuite ce registre pour construire les objets au
     * moment de l'injection de dépendances.
     *
     * @param string $class
     * @param string $classImpl
     * @return void
     */
    public function registerDependency(string $class, string $classImpl): void
    {
        $this->dependencies[$class] = $classImpl;
    }

    /**
     * Retourne le registre des dépendances déjà déclarées.
     *
     * @return array<string, string>
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    /**
     * Retourne l'instance de contexte d'exécution actuellement associée.
     *
     * @return FrameworkContext
     */
    public function getFrameworkContextImpl(): FrameworkContext
    {
        return $this->frameworkContextImpl;
    }

    /**
     * Injecte l'implémentation du contexte d'exécution du framework.
     *
     * @param FrameworkContext $frameworkContextImpl
     * @return MiniPhpRestConfig
     */
    public function setFrameworkContextImpl(FrameworkContext $frameworkContextImpl): MiniPhpRestConfig
    {
        $this->frameworkContextImpl = $frameworkContextImpl;
        return $this;
    }

    public function getTransformDataOnErrorFunction(): ?callable
    {
        return $this->transformDataOnErrorFunction;
    }

    public function setTransformDataOnErrorFunction(?callable $transformDataOnErrorFunction): MiniPhpRestConfig
    {
        $this->transformDataOnErrorFunction = $transformDataOnErrorFunction;
        return $this;
    }






}