<?php
// Inclure l'autoloader
require_once __DIR__ . '/autoload.php';

// Démarrer la session
session_start();

// Vérifier si le combat est initialisé
if (!isset($_SESSION['combat'])) {
    header('Location: index.php');
    exit;
}

// Récupérer les données de session nécessaires
$combat = $_SESSION['combat'];
$pokemon1 = $combat->getPokemon1();
$pokemon2 = $combat->getPokemon2();
$tourActuel = $combat->getTourActuel();
$joueurActif = $combat->getJoueurActif();
$messages = $_SESSION['messages'] ?? [];

// Traitement des actions de combat
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['pokemon'])) {
        $action = $_POST['action'];
        $pokemonNumber = (int)$_POST['pokemon'];
        
        // Vérifier si c'est bien le tour du joueur
        if ($pokemonNumber === $joueurActif) {
            $attaquant = $pokemonNumber === 1 ? $pokemon1 : $pokemon2;
            $defenseur = $pokemonNumber === 1 ? $pokemon2 : $pokemon1;
            
            $message = $combat->tourDeCombat($action, $attaquant, $defenseur);
            $_SESSION['messages'][] = $message;
            
            // Sauvegarder l'état du combat
            $_SESSION['combat'] = $combat;
            
            // Rediriger pour éviter la resoumission du formulaire
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }
}

// Message de début de combat si c'est le premier tour
if ($tourActuel === 1 && empty($messages)) {
    $_SESSION['messages'][] = "Le combat commence entre {$pokemon1->getNom()} et {$pokemon2->getNom()} !";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Combat Pokémon</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .tour-info {
            text-align: center;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .pokemon-info {
            border: 1px solid #ddd;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .pokemon-info.active {
            border: 2px solid #4CAF50;
            box-shadow: 0 0 5px rgba(76,175,80,0.5);
        }
        
        .pokemon-info.inactive {
            opacity: 0.7;
        }
        
        .hp-bar {
            width: 100%;
            height: 20px;
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .hp-bar-fill {
            height: 100%;
            background-color: #4CAF50;
            transition: width 0.3s ease;
        }
        
        .actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        button {
            padding: 8px 16px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        button:hover {
            background-color: #45a049;
        }
        
        .battle-log {
            margin-top: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .battle-log p {
            margin: 5px 0;
            padding: 5px;
            border-bottom: 1px solid #eee;
        }
        
        .battle-end {
            text-align: center;
            margin-top: 20px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        
        .return-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
        }
        
        .return-button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="tour-info">
            <h2>Tour <?php echo $tourActuel; ?></h2>
            <p>C'est au tour de <?php echo $joueurActif === 1 ? $pokemon1->getNom() : $pokemon2->getNom(); ?> de jouer !</p>
        </div>

        <!-- Pokémon 1 -->
        <div class="pokemon-info <?php echo $joueurActif === 1 ? 'active' : 'inactive'; ?>">
            <h2><?php echo $pokemon1->getNom(); ?> (<?php echo $pokemon1->getType(); ?>)</h2>
            <div class="hp-bar">
                <?php $pourcentageVie1 = ($pokemon1->getPointsDeVie() / $pokemon1->getPvMax()) * 100; ?>
                <div class="hp-bar-fill" style="width: <?php echo $pourcentageVie1; ?>%"></div>
            </div>
            <p>PV: <?php echo $pokemon1->getPointsDeVie(); ?>/<?php echo $pokemon1->getPvMax(); ?></p>
            
            <?php if (!$pokemon1->estKo() && !$combat->estTermine() && $joueurActif === 1): ?>
                <div class="actions">
                    <form method="POST">
                        <input type="hidden" name="pokemon" value="1">
                        <button type="submit" name="action" value="Attaquer">Attaque normale</button>
                        <button type="submit" name="action" value="Attaque Spéciale">Attaque spéciale</button>
                        <button type="submit" name="action" value="Soigner">Se soigner</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pokémon 2 -->
        <div class="pokemon-info <?php echo $joueurActif === 2 ? 'active' : 'inactive'; ?>">
            <h2><?php echo $pokemon2->getNom(); ?> (<?php echo $pokemon2->getType(); ?>)</h2>
            <div class="hp-bar">
                <?php $pourcentageVie2 = ($pokemon2->getPointsDeVie() / $pokemon2->getPvMax()) * 100; ?>
                <div class="hp-bar-fill" style="width: <?php echo $pourcentageVie2; ?>%"></div>
            </div>
            <p>PV: <?php echo $pokemon2->getPointsDeVie(); ?>/<?php echo $pokemon2->getPvMax(); ?></p>
            
            <?php if (!$pokemon2->estKo() && !$combat->estTermine() && $joueurActif === 2): ?>
                <div class="actions">
                    <form method="POST">
                        <input type="hidden" name="pokemon" value="2">
                        <button type="submit" name="action" value="Attaquer">Attaque normale</button>
                        <button type="submit" name="action" value="Attaque Spéciale">Attaque spéciale</button>
                        <button type="submit" name="action" value="Soigner">Se soigner</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <!-- Journal de combat -->
        <div class="battle-log">
            <?php foreach (array_reverse($_SESSION['messages']) as $message): ?>
                <p><?php echo htmlspecialchars($message); ?></p>
            <?php endforeach; ?>
        </div>

        <!-- Fin de combat -->
        <?php if ($combat->estTermine()): ?>
            <div class="battle-end">
                <h2>Combat terminé !</h2>
                <?php
                $vainqueur = $combat->getVainqueur();
                if ($vainqueur) {
                    echo "<p>Le vainqueur est " . htmlspecialchars($vainqueur->getNom()) . " !</p>";
                }
                ?>
                <a href="index.php" class="return-button">Retour au menu principal</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>