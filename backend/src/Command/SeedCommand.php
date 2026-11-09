<?php

namespace App\Command;

use App\Document\TestSession;
use App\Document\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:seed', description: 'Crée les index MongoDB et des données de démonstration (idempotent).')]
class SeedCommand extends Command
{
    public function __construct(
        private readonly DocumentManager $dm,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->dm->getSchemaManager()->ensureIndexes();
        $output->writeln('Index MongoDB créés.');

        $users = $this->dm->getRepository(User::class);
        foreach ([
            ['Admin', 'admin@ets-test.com', 'Hello@ETS', ['ROLE_ADMIN']],
            ['Soufiane HAMOUTI', 's.hamouti@ets-test.com', 'Hello@ETS', []],
        ] as [$name, $email, $password, $roles]) {
            if (null === $users->findOneBy(['email' => $email])) {
                $user = (new User())->setName($name)->setEmail($email)->setRoles($roles);
                $user->setPassword($this->hasher->hashPassword($user, $password));
                $this->dm->persist($user);
                $output->writeln("Utilisateur créé : $email");
            }
        }

        if (0 === (int) $this->dm->createQueryBuilder(TestSession::class)->count()->getQuery()->execute()) {
            $base = new \DateTimeImmutable('+7 days');
            $samples = [
                ['Anglais', '09:00', 'Paris', 20, 950.0],
                ['Anglais', '14:00', 'Lyon', 15, 950.0],
                ['Français', '10:00', 'Paris', 12, 850.0],
                ['Espagnol', '11:00', 'Madrid', 10, 900.0],
                ['Allemand', '09:30', 'Berlin', 8, 900.0],
                ['Italien', '15:00', 'Milan', 2, 800.0],
                ['Anglais', '10:30', 'Londres', 25, 1100.0],
                ['Français', '13:00', 'Bruxelles', 14, 850.0],
                ['Espagnol', '16:00', 'Barcelone', 9, 900.0],
                ['Allemand', '08:30', 'Munich', 11, 950.0],
                ['Italien', '12:00', 'Rome', 6, 800.0],
                ['Anglais', '17:00', 'Dublin', 18, 1050.0],
            ];
            foreach ($samples as $i => [$language, $time, $location, $places, $price]) {
                $this->dm->persist(
                    (new TestSession())
                        ->setLanguage($language)
                        ->setDate($base->modify(sprintf('+%d days', $i))->format('Y-m-d'))
                        ->setTime($time)
                        ->setLocation($location)
                        ->setPrice($price)
                        ->setPlaces($places)
                        ->setRemaining($places)
                );
            }
            $output->writeln(sprintf('%d sessions créées.', count($samples)));
        }

        $defaultPrices = ['Anglais' => 950.0, 'Français' => 850.0, 'Espagnol' => 900.0, 'Allemand' => 900.0, 'Italien' => 800.0];
        $withoutPrice = $this->dm->createQueryBuilder(TestSession::class)->field('price')->exists(false)->getQuery()->execute();
        $backfilled = 0;
        foreach ($withoutPrice as $session) {
            $session->setPrice($defaultPrices[$session->getLanguage()] ?? 800.0);
            ++$backfilled;
        }
        if ($backfilled > 0) {
            $output->writeln(sprintf('Prix ajouté à %d session(s) existante(s).', $backfilled));
        }

        $this->dm->flush();

        return Command::SUCCESS;
    }
}
