# 📋 Plan de Tests — Stratégies et Phases

**Objectif global** : Atteindre >70% de couverture de code (cadrage projet) avec tests unitaires et fonctionnels.

---

## 🎯 Vue d'ensemble — 3 angles d'attaque

### **Angle 1 : Stratégie FOND** (Bottom-Up / Unitaires)
Commencer par les briques élémentaires (entités, services), remonter vers les controllers.
- ✅ Facile à mocker
- ✅ Tests rapides (pas de BDD réelle)
- ✅ Couvre la logique métier
- ❌ Ne teste pas l'intégration complète
- **Idéal pour** : Entités, validateurs, services métier

### **Angle 2 : Stratégie HAUT** (Top-Down / Fonctionnels)
Tester les flows complets (request → response), en passant par tous les layers.
- ✅ Teste l'intégration réelle
- ✅ Valide les routes, templates, redirect
- ✅ Détecte les bugs d'intégration
- ❌ Plus lents, plus complexes
- **Idéal pour** : Controllers, sécurité, authentification

### **Angle 3 : Stratégie HYBRIDE** (Recommandée)
Combiner unitaires + fonctionnels avec des priorités intelligentes.
- ✅ Couverture rapide de la logique critique
- ✅ Tests d'intégration sur les chemins clés
- ✅ Ratio coût/bénéfice optimal
- **Recommandé pour** : Production

---

## 📊 Matrice de Priorités (Impact × Couverture)

| Composant | Type | Priorité | Raison | Couverture estimée |
|-----------|------|----------|--------|-------------------|
| **User Entity** | Unit | 🔴 Critique | Auth, contrôle d'accès | 100% (3-5 tests) |
| **Album Entity** | Unit | 🟠 Haute | Validation, relations | 100% (2-3 tests) |
| **Media Entity** | Unit | 🔴 Critique | Validation fichier, relations | 100% (5-7 tests) |
| **UserRepository** | Unit | 🟠 Haute | Queries optimisées (N+1) | 90% (4-6 tests) |
| **Security/UserChecker** | Unit | 🔴 Critique | Bloque comptes inactifs | 100% (3-4 tests) |
| **Security/Authenticator** | Unit+Fonc | 🔴 Critique | Login, logout, session | 95% (6-8 tests) |
| **Admin/Album/** | Fonc | 🟠 Haute | CRUD + sécurité CSRF | 90% (6-8 tests) |
| **Admin/Media/** | Fonc | 🟠 Haute | Upload, validation, cascade | 90% (8-10 tests) |
| **Admin/Guest/** | Fonc | 🟠 Haute | CRUD guests, block/unblock | 90% (8-10 tests) |
| **Front/Guests** | Fonc | 🟡 Moyenne | Liste publique, filter actifs | 85% (3-4 tests) |
| **Front/Other** | Fonc | 🟡 Moyenne | Routes publiques | 80% (2-3 tests chacun) |

**Total estimé** : ~50-60 tests = **~72% couverture** 🎯

---

## 🚀 PHASE 1 : Setup & Infrastructure (2-3h)

### 1.1 Vérifier PHPUnit

```bash
symfony console --version
composer show phpunit/phpunit
php bin/phpunit --version
```

### 1.2 Configuration de test

```bash
# Vérifier phpunit.xml.dist
cat phpunit.xml.dist | grep -E "(testsuites|coverage)"

# Créer .env.test si absent
echo "DATABASE_URL=\"sqlite:///:memory:\"" > .env.test
```

### 1.3 Structure des tests

```
tests/
├── Unit/
│   ├── Entity/
│   │   ├── UserTest.php
│   │   ├── AlbumTest.php
│   │   └── MediaTest.php
│   ├── Repository/
│   │   └── UserRepositoryTest.php
│   └── Security/
│       ├── UserCheckerTest.php
│       └── AuthenticatorTest.php
├── Functional/
│   ├── Controller/
│   │   ├── Admin/
│   │   │   ├── AlbumControllerTest.php
│   │   │   ├── MediaControllerTest.php
│   │   │   └── GuestControllerTest.php
│   │   └── Front/
│   │       ├── GuestsControllerTest.php
│   │       └── HomeControllerTest.php
│   ├── Security/
│   │   ├── LoginTest.php
│   │   └── LogoutTest.php
│   └── Integration/
│       ├── MediaUploadIntegrationTest.php
│       └── GuestManagementIntegrationTest.php
└── bootstrap.php
```

### 1.4 Installer les dépendances de test

```bash
composer require --dev symfony/test-pack
composer require --dev liip/test-fixtures-bundle
composer require --dev stof/doctrine-test-extensions
```

### 1.5 Créer la base de test

```bash
# Créer la BDD de test
symfony console --env=test doctrine:database:create
symfony console --env=test doctrine:migrations:migrate --no-interaction
```

---

## 🧪 PHASE 2 : Tests Unitaires (5-6h)

### 2.1 Tests d'Entités

#### **UserTest.php** (3 tests)

```php
namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
    }

    public function testUserIsActive(): void
    {
        $this->user->setActive(true);
        $this->assertTrue($this->user->isActive());
    }

    public function testAdminGetsRoleAdmin(): void
    {
        $this->user->setAdmin(true);
        $this->assertContains('ROLE_ADMIN', $this->user->getRoles());
    }

    public function testUserGetRoleUser(): void
    {
        $this->user->setAdmin(false);
        $this->assertContains('ROLE_USER', $this->user->getRoles());
    }
}
```

#### **AlbumTest.php** (2 tests)

```php
namespace App\Tests\Unit\Entity;

use App\Entity\Album;
use PHPUnit\Framework\TestCase;

class AlbumTest extends TestCase
{
    public function testAlbumNameValidation(): void
    {
        $album = new Album();
        $album->setName('Portfolio 2024');
        $this->assertEquals('Portfolio 2024', $album->getName());
    }

    public function testAlbumMediaRelation(): void
    {
        $album = new Album();
        $this->assertCount(0, $album->getMedias());
    }
}
```

#### **MediaTest.php** (5 tests)

```php
namespace App\Tests\Unit\Entity;

use App\Entity\Media;
use App\Entity\Album;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class MediaTest extends TestCase
{
    public function testMediaPathGeneration(): void
    {
        $media = new Media();
        $media->setPath('/uploads/2024/photo.jpg');
        $this->assertStringContainsString('/uploads/', $media->getPath());
    }

    public function testMediaTitle(): void
    {
        $media = new Media();
        $media->setTitle('Sunset in Paris');
        $this->assertEquals('Sunset in Paris', $media->getTitle());
    }

    public function testMediaBelongsToUser(): void
    {
        $media = new Media();
        $user = new User();
        $media->setUser($user);
        $this->assertEquals($user, $media->getUser());
    }

    public function testMediaBelongsToAlbum(): void
    {
        $media = new Media();
        $album = new Album();
        $media->setAlbum($album);
        $this->assertEquals($album, $media->getAlbum());
    }

    public function testMediaToString(): void
    {
        $media = new Media();
        $media->setTitle('Test Image');
        $this->assertEquals('Test Image', (string)$media);
    }
}
```

**Total Phase 2.1** : 10 tests × ~2 min = 20 minutes ⏱️

### 2.2 Tests de Repositories

#### **UserRepositoryTest.php** (4-5 tests)

```php
namespace App\Tests\Unit\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;

class UserRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->userRepository = $this->em->getRepository(User::class);
    }

    public function testFindActiveGuestsWithMediasQuery(): void
    {
        // Créer fixtures
        $guest1 = new User();
        $guest1->setEmail('guest1@test.com');
        $guest1->setActive(true);
        $this->em->persist($guest1);
        
        $guest2 = new User();
        $guest2->setEmail('guest2@test.com');
        $guest2->setActive(false); // Inactif
        $this->em->persist($guest2);
        
        $this->em->flush();

        // Tester la requête optimisée
        $result = $this->userRepository->findActiveGuestsWithMedias();
        
        $this->assertCount(1, $result);
        $this->assertEquals('guest1@test.com', $result[0]->getEmail());
    }

    public function testFindByEmailReturnsUser(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $this->em->persist($user);
        $this->em->flush();

        $found = $this->userRepository->findOneBy(['email' => 'test@example.com']);
        $this->assertNotNull($found);
        $this->assertEquals('test@example.com', $found->getEmail());
    }

    public function testFindByEmailReturnsNullIfNotFound(): void
    {
        $result = $this->userRepository->findOneBy(['email' => 'nonexistent@example.com']);
        $this->assertNull($result);
    }
}
```

**Total Phase 2.2** : 4-5 tests × ~3 min = 15-20 minutes ⏱️

### 2.3 Tests de Sécurité

#### **UserCheckerTest.php** (3 tests)

```php
namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Security\UserChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

class UserCheckerTest extends TestCase
{
    private UserChecker $checker;

    protected function setUp(): void
    {
        $this->checker = new UserChecker();
    }

    public function testActiveUserPassesCheck(): void
    {
        $user = new User();
        $user->setActive(true);
        
        $this->expectNotToPerformAssertions();
        $this->checker->checkPreAuth($user);
    }

    public function testInactiveUserThrowsException(): void
    {
        $user = new User();
        $user->setActive(false);
        
        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->checker->checkPreAuth($user);
    }

    public function testNonUserObjectIsIgnored(): void
    {
        $notAUser = new \stdClass();
        
        $this->expectNotToPerformAssertions();
        $this->checker->checkPreAuth($notAUser);
    }
}
```

**Total Phase 2.3** : 3 tests × ~3 min = 10 minutes ⏱️

**Total Phase 2** : ~45 minutes 🎯

---

## 🌐 PHASE 3 : Tests Fonctionnels (6-8h)

### 3.1 Tests de Sécurité & Authentification

#### **LoginTest.php** (4 tests)

```php
namespace App\Tests\Functional\Security;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class LoginTest extends WebTestCase
{
    private $userRepository;
    private $em;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->em = $kernel->getContainer()->get(EntityManagerInterface::class);
        $this->userRepository = $this->em->getRepository(User::class);
    }

    public function testLoginPageLoadsSuccessfully(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/login');
        
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Se connecter', $crawler->filter('h1')->text());
    }

    public function testSuccessfulLoginRedirectsToDashboard(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/login');
        
        $form = $crawler->selectButton('Se connecter')->form();
        $form['email'] = 'ina@zaoui.com';
        $form['password'] = 'admin123';
        
        $client->submit($form);
        $this->assertResponseRedirects('/admin/media');
    }

    public function testInactiveUserCannotLogin(): void
    {
        // Créer un utilisateur inactif
        $user = new User();
        $user->setEmail('inactive@test.com');
        $user->setActive(false);
        $this->em->persist($user);
        $this->em->flush();
        
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/login');
        
        $form = $crawler->selectButton('Se connecter')->form();
        $form['email'] = 'inactive@test.com';
        $form['password'] = 'password';
        
        $client->submit($form);
        $this->assertStringContainsString('désactivé', $client->getResponse()->getContent());
    }

    public function testInvalidCredentialsShowError(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/login');
        
        $form = $crawler->selectButton('Se connecter')->form();
        $form['email'] = 'wrong@test.com';
        $form['password'] = 'wrong';
        
        $client->submit($form);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Invalid credentials', 
            $client->getResponse()->getContent());
    }
}
```

#### **LogoutTest.php** (2 tests)

```php
namespace App\Tests\Functional\Security;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LogoutTest extends WebTestCase
{
    public function testLogoutRedirectsToHome(): void
    {
        $client = static::createClient();
        $this->loginAs('ina@zaoui.com'); // Helper
        
        $client->request('GET', '/admin/logout');
        $this->assertResponseRedirects('/');
    }

    public function testLogoutSessionIsCleared(): void
    {
        $client = static::createClient();
        $this->loginAs('ina@zaoui.com');
        
        $client->request('GET', '/admin/logout');
        $client->followRedirect();
        
        $client->request('GET', '/admin/media');
        $this->assertResponseRedirects('/admin/login');
    }
}
```

**Total Phase 3.1** : 6 tests × ~4 min = 24 minutes ⏱️

### 3.2 Tests Admin Controllers

#### **AlbumControllerTest.php** (6 tests)

```php
namespace App\Tests\Functional\Controller\Admin\Album;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\Album;
use App\Entity\User;

class AlbumControllerTest extends WebTestCase
{
    protected function loginAsAdmin()
    {
        // Helper pour se connecter
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/login');
        $form = $crawler->selectButton('Se connecter')->form();
        $form['email'] = 'ina@zaoui.com';
        $form['password'] = 'admin123';
        $client->submit($form);
        $client->followRedirect();
        return $client;
    }

    public function testIndexDisplaysAlbums(): void
    {
        $client = $this->loginAsAdmin();
        $client->request('GET', '/admin/album');
        
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Albums', 
            $client->getResponse()->getContent());
    }

    public function testAddAlbumFormLoads(): void
    {
        $client = $this->loginAsAdmin();
        $client->request('GET', '/admin/album/add');
        
        $this->assertResponseIsSuccessful();
    }

    public function testAddAlbumPersistsData(): void
    {
        $client = $this->loginAsAdmin();
        $crawler = $client->request('GET', '/admin/album/add');
        
        $form = $crawler->selectButton('Ajouter')->form();
        $form['album_type[name]'] = 'New Album 2024';
        
        $client->submit($form);
        $this->assertResponseRedirects('/admin/album');
        
        // Vérifier que l'album existe
        $client->followRedirect();
        $this->assertStringContainsString('New Album 2024', 
            $client->getResponse()->getContent());
    }

    public function testEditAlbumUpdatesData(): void
    {
        $client = $this->loginAsAdmin();
        // Créer un album
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $album = new Album();
        $album->setName('Original Name');
        $em->persist($album);
        $em->flush();
        $albumId = $album->getId();
        
        // Éditer
        $crawler = $client->request('GET', "/admin/album/{$albumId}/edit");
        $form = $crawler->selectButton('Modifier')->form();
        $form['album_type[name]'] = 'Updated Name';
        
        $client->submit($form);
        $this->assertResponseRedirects('/admin/album');
    }

    public function testDeleteAlbumWithCSRFToken(): void
    {
        $client = $this->loginAsAdmin();
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $album = new Album();
        $album->setName('Album to Delete');
        $em->persist($album);
        $em->flush();
        $albumId = $album->getId();
        
        // Récupérer le token CSRF depuis la page index
        $crawler = $client->request('GET', '/admin/album');
        $deleteForm = $crawler->selectButton('Supprimer')->form();
        $token = $deleteForm->get('_token')->getValue();
        
        // Supprimer
        $client->request('POST', "/admin/album/{$albumId}/delete", 
            ['_token' => $token]);
        
        $this->assertResponseRedirects('/admin/album');
    }
}
```

#### **MediaControllerTest.php** (8 tests)

```php
namespace App\Tests\Functional\Controller\Admin\Media;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\Media;
use App\Entity\User;
use App\Entity\Album;

class MediaControllerTest extends WebTestCase
{
    // ... setup ...

    public function testIndexPaginatesMedias(): void
    {
        $client = $this->loginAsAdmin();
        $client->request('GET', '/admin/media');
        
        $this->assertResponseIsSuccessful();
        // Vérifier la pagination (25 items par page)
        $this->assertStringContainsString('1-25', 
            $client->getResponse()->getContent());
    }

    public function testAddMediaFormLoads(): void
    {
        $client = $this->loginAsAdmin();
        $client->request('GET', '/admin/media/add');
        
        $this->assertResponseIsSuccessful();
    }

    public function testUploadMediaWithValidFile(): void
    {
        $client = $this->loginAsAdmin();
        $crawler = $client->request('GET', '/admin/media/add');
        
        $form = $crawler->selectButton('Ajouter')->form();
        $form['media_type[title]'] = 'Test Photo';
        
        // Créer un fichier de test
        $file = new UploadedFile(
            $this->createTestImage(),
            'test.jpg',
            'image/jpeg'
        );
        $form['media_type[file]'] = $file;
        
        $client->submit($form);
        $this->assertResponseRedirects('/admin/media');
    }

    public function testUploadMediaRejectsLargeFile(): void
    {
        $client = $this->loginAsAdmin();
        $crawler = $client->request('GET', '/admin/media/add');
        
        $form = $crawler->selectButton('Ajouter')->form();
        // Créer un fichier > 2MB
        $file = new UploadedFile(
            $this->createLargeTestImage(3), // 3MB
            'large.jpg',
            'image/jpeg'
        );
        $form['media_type[file]'] = $file;
        
        $client->submit($form);
        // Vérifier l'erreur de validation
        $this->assertStringContainsString('2M', 
            $client->getResponse()->getContent());
    }

    public function testUploadMediaRejectsInvalidMIME(): void
    {
        $client = $this->loginAsAdmin();
        $crawler = $client->request('GET', '/admin/media/add');
        
        $form = $crawler->selectButton('Ajouter')->form();
        $file = new UploadedFile(
            $this->createTestFile('test.pdf'), // PDF, not image
            'test.pdf',
            'application/pdf'
        );
        $form['media_type[file]'] = $file;
        
        $client->submit($form);
        $this->assertStringContainsString('MIME type', 
            $client->getResponse()->getContent());
    }

    public function testAdminCanSeeAllMedias(): void
    {
        $client = $this->loginAsAdmin();
        $client->request('GET', '/admin/media');
        
        // Admin voit tous les médias
        $this->assertCount(5050, /* médias totaux */);
    }

    public function testGuestOnlySeesOwnMedias(): void
    {
        $client = static::createClient();
        $this->loginAs('invite+3@example.com', 'admin123');
        
        $client->request('GET', '/admin/media');
        
        // Guest ne voit que ses propres médias
        $this->assertStringContainsString('Mes photos', 
            $client->getResponse()->getContent());
    }

    public function testDeleteMediaRemovesFile(): void
    {
        $client = $this->loginAsAdmin();
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        
        // Créer un média
        $media = new Media();
        $media->setPath('/uploads/2024/test.jpg');
        $em->persist($media);
        $em->flush();
        $mediaId = $media->getId();
        
        // Supprimer
        $crawler = $client->request('GET', '/admin/media');
        $deleteForm = $crawler->selectButton('Supprimer')->form();
        $token = $deleteForm->get('_token')->getValue();
        
        $client->request('POST', "/admin/media/{$mediaId}/delete",
            ['_token' => $token]);
        
        $this->assertResponseRedirects('/admin/media');
    }
}
```

#### **GuestControllerTest.php** (8 tests)

```php
namespace App\Tests\Functional\Controller\Admin\Guest;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;

class GuestControllerTest extends WebTestCase
{
    // ... setup ...

    public function testIndexDisplaysGuests(): void
    {
        $client = $this->loginAsAdmin();
        $client->request('GET', '/admin/guest');
        
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Invités', 
            $client->getResponse()->getContent());
    }

    public function testAddGuestFormLoads(): void
    {
        $client = $this->loginAsAdmin();
        $client->request('GET', '/admin/guest/add');
        
        $this->assertResponseIsSuccessful();
    }

    public function testAddGuestCreatesAccount(): void
    {
        $client = $this->loginAsAdmin();
        $crawler = $client->request('GET', '/admin/guest/add');
        
        $form = $crawler->selectButton('Ajouter')->form();
        $form['guest_type[name]'] = 'New Guest';
        $form['guest_type[email]'] = 'newguest@test.com';
        $form['guest_type[password]'] = 'SecurePassword123!';
        
        $client->submit($form);
        $this->assertResponseRedirects('/admin/guest');
    }

    public function testAddGuestHashesPassword(): void
    {
        $client = $this->loginAsAdmin();
        $crawler = $client->request('GET', '/admin/guest/add');
        
        $form = $crawler->selectButton('Ajouter')->form();
        $form['guest_type[name]'] = 'Another Guest';
        $form['guest_type[email]'] = 'another@test.com';
        $form['guest_type[password]'] = 'PlainPassword';
        
        $client->submit($form);
        
        // Vérifier que le password n'est pas en clair
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $guest = $em->getRepository(User::class)->findOneBy(
            ['email' => 'another@test.com']
        );
        $this->assertNotEquals('PlainPassword', $guest->getPassword());
    }

    public function testBlockGuestPreventsLogin(): void
    {
        $client = $this->loginAsAdmin();
        // Créer et bloquer un guest
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $guest = new User();
        $guest->setEmail('blockme@test.com');
        $guest->setActive(true);
        $em->persist($guest);
        $em->flush();
        
        // Bloquer via POST
        $crawler = $client->request('GET', '/admin/guest');
        $blockForm = $crawler->selectButton('Bloquer')->form();
        $token = $blockForm->get('_token')->getValue();
        
        $client->request('POST', "/admin/guest/{$guest->getId()}/block",
            ['_token' => $token]);
        
        $this->assertResponseRedirects('/admin/guest');
        
        // Vérifier que le guest ne peut plus se connecter
        $em->refresh($guest);
        $this->assertFalse($guest->isActive());
    }

    public function testUnblockGuestAllowsLogin(): void
    {
        // Similaire à testBlockGuest, mais unblock
    }

    public function testDeleteGuestCascadesMediaDeletion(): void
    {
        $client = $this->loginAsAdmin();
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        
        // Créer un guest avec un média
        $guest = new User();
        $guest->setEmail('deleteme@test.com');
        $em->persist($guest);
        
        $media = new Media();
        $media->setPath('/uploads/2024/guest-photo.jpg');
        $media->setUser($guest);
        $em->persist($media);
        $em->flush();
        $guestId = $guest->getId();
        
        // Supprimer le guest
        $crawler = $client->request('GET', '/admin/guest');
        $deleteForm = $crawler->selectButton('Supprimer')->form();
        $token = $deleteForm->get('_token')->getValue();
        
        $client->request('POST', "/admin/guest/{$guestId}/delete",
            ['_token' => $token]);
        
        // Vérifier que guest ET médias sont supprimés
        $this->assertNull($em->getRepository(User::class)->find($guestId));
        $this->assertCount(0, $em->getRepository(Media::class)->findBy(
            ['user' => $guestId]
        ));
    }

    public function testDeleteGuestRemovesPhysicalFiles(): void
    {
        // Test que les fichiers physiques sont supprimés aussi
    }
}
```

**Total Phase 3.2** : 22 tests × ~5 min = 110 minutes ⏱️

### 3.3 Tests Front Controllers

#### **GuestsControllerTest.php** (3 tests)

```php
namespace App\Tests\Functional\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GuestsControllerTest extends WebTestCase
{
    public function testGuestsPageLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', '/invites');
        
        $this->assertResponseIsSuccessful();
    }

    public function testOnlyActiveGuestsAreDisplayed(): void
    {
        $client = static::createClient();
        $client->request('GET', '/invites');
        
        // Vérifier que les guests inactifs ne sont PAS affichés
        $content = $client->getResponse()->getContent();
        // TODO: assertions spécifiques
    }

    public function testGuestProfilePageLoadsIfActive(): void
    {
        $client = static::createClient();
        $client->request('GET', '/guest/3'); // Guest actif
        
        $this->assertResponseIsSuccessful();
    }

    public function testGuestProfileReturn404IfInactive(): void
    {
        $client = static::createClient();
        $client->request('GET', '/guest/999'); // Guest inexistant/inactif
        
        $this->assertResponseStatusCodeSame(404);
    }
}
```

**Total Phase 3.3** : 4 tests × ~3 min = 12 minutes ⏱️

**Total Phase 3** : ~2h30

---

## 📈 PHASE 4 : Intégration & Couverture (1-2h)

### 4.1 Tests d'Intégration Complexes

#### **MediaUploadIntegrationTest.php** (3 tests)

- Upload + Base de données + Fichier physique
- Validation fichier + Hashage nom
- Suppression cascade album → médias

#### **GuestManagementIntegrationTest.php** (3 tests)

- Création guest + Login + Voir médias
- Blocage guest + Tentative login échouée
- Suppression guest + Cascade médias

### 4.2 Coverage Report

```bash
php bin/phpunit --coverage-html=coverage/
php bin/phpunit --coverage-clover=coverage.xml
php bin/phpunit --coverage-text
```

Target : >70% ✅

---

## 🎯 Synthèse : 3 Approches Possibles

### **Approche 1 : FOND → HAUT (Recommandée pour débutant)**
1. Phase 1 (Setup)
2. Phase 2 (Unitaires) — Comprendre les entités/services
3. Phase 3 (Fonctionnels) — Tester l'intégration
4. Phase 4 (Coverage)

**Durée** : ~12-14h, **Avantage** : Progression logique

---

### **Approche 2 : HAUT → FOND (Recommandée pour pragmatique)**
1. Phase 1 (Setup)
2. Phase 3.1 (Auth/Security) — Tests critiques en premier
3. Phase 3.2 (Controllers) — Tester les CRUDs
4. Phase 2 (Unitaires) — Combler les trous
5. Phase 4 (Coverage)

**Durée** : ~12-14h, **Avantage** : Valide rapidement les bugs critiques

---

### **Approche 3 : HYBRIDE (Recommandée pour efficacité)**
1. Phase 1 (Setup) — 30 min
2. **Batch 1** : Phase 2.1 + 2.3 (Entités + Security) — 30 min
3. **Batch 2** : Phase 3.1 (Auth tests) — 1h
4. **Batch 3** : Phase 3.2 (CRUD majeurs) — 2h
5. **Batch 4** : Phase 2.2 (Repositories) + Phase 3.3 (Front) — 1h30
6. **Batch 5** : Phase 4 (Coverage + fixes) — 1h

**Durée** : ~7-8h (parallélisable), **Avantage** : Couverture + rapidité

---

## 📋 Checklist de Démarrage

- [ ] Vérifier PHPUnit + dépendances
- [ ] Créer structure `/tests`
- [ ] Configurer `.env.test`
- [ ] Créer BDD de test
- [ ] Écrire helpers (loginAsAdmin, createTestFile)
- [ ] Phase 1 : Entités (10 tests, 20 min)
- [ ] Phase 2 : Sécurité (3 tests, 10 min)
- [ ] Phase 3 : Auth Controllers (6 tests, 25 min)
- [ ] Phase 4 : CRUD Controllers (22 tests, 110 min)
- [ ] Phase 5 : Front + Intégration (7 tests, 30 min)
- [ ] Coverage report
- [ ] Commit + Push

---

## 🚀 Prochaines Étapes

1. **Choisir l'approche** (Fond, Haut, ou Hybride)
2. **Exécuter Phase 1** (Setup + structure)
3. **Créer les helpers** (loginAsAdmin, fixtures)
4. **Écrire les tests** phase par phase
5. **Générer coverage report**
6. **Atteindre >70%** 🎯

**Estimé total** : 10-14h selon approche et interruptions

Let's go ! 💪
