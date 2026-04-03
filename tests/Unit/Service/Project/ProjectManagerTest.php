<?php

namespace App\Tests\Unit\Service\Project;

use App\Entity\Project;
use App\Exception\EntityNotFoundException;
use App\Repository\ProjectRepository;
use App\Service\Project\ProjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class ProjectManagerTest extends TestCase
{
    private ProjectRepository&MockObject $projectRepository;
    private ProjectManager $projectManager;

    protected function setUp(): void
    {
        $this->projectRepository = $this->createMock(ProjectRepository::class);
        $this->projectManager = new ProjectManager($this->projectRepository);
    }

    public function testGetProjectReturnsProject(): void
    {
        $id = Uuid::v7();
        $project = new Project('Test Project', 'TST');

        $this->projectRepository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($project);

        $result = $this->projectManager->get($id);

        $this->assertEquals($project, $result);
    }

    public function testGetProjectThrowsEntityNotFoundExceptionWhenNotFound(): void
    {
        $id = Uuid::v7();

        $this->projectRepository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn(null);

        $this->expectException(EntityNotFoundException::class);

        $this->projectManager->get($id);
    }
}
